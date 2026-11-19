<?php

namespace App\Http\Controllers\Pro;

use App\Enums\BillingCycle;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pro\CancelSubscriptionRequest;
use App\Http\Requests\Pro\SubscribePlanRequest;
use App\Models\Business;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\Plan;
use App\Models\PricingSheet;
use App\Models\ProviderProfile;
use App\Models\ProviderProfileMedia;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Monetization\EntitlementService;
use App\Services\Monetization\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Espace « Mon abonnement » du professionnel.
 *
 * Le propriétaire est toujours explicite : un profil indépendant souscrit
 * pour lui-même, une entreprise couvre tous ses profils. Aucun utilisateur ne
 * peut souscrire ni résilier pour un propriétaire dont il n'a pas la charge.
 */
class SubscriptionController extends Controller
{
    public function __construct(
        private readonly EntitlementService $entitlements,
        private readonly SubscriptionService $subscriptions,
    ) {}

    /**
     * Affiche l'abonnement courant du propriétaire sélectionné.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $ownerModels = $this->ownerModels($user);
        $owner = $this->selectedOwner($request, $ownerModels);
        $subscription = $owner !== null ? $this->subscriptionFor($owner) : null;
        $effectivePlan = $this->effectivePlan($owner);

        return Inertia::render('pro/Subscription', [
            'owners' => $ownerModels->map(fn ($item) => $this->ownerPayload($item))->values()->all(),
            'selectedOwner' => $owner !== null ? $this->ownerPayload($owner) : null,
            'subscription' => $subscription !== null ? $this->subscriptionPayload($subscription) : null,
            'currentPlan' => $this->entitlements->planPayload($effectivePlan),
            'limits' => $owner !== null ? $this->limitsForOwner($owner, $effectivePlan, $subscription) : [],
            'features' => $this->entitlements->featuresForPlan($effectivePlan),
            'history' => $subscription !== null ? $this->historyPayload($subscription) : [],
            'payments' => $subscription !== null ? $this->paymentsPayload($subscription) : [],
            'pendingPayment' => $subscription !== null
                ? collect($this->paymentsPayload($subscription))->firstWhere('status', 'pending')
                : null,
            'paymentInstructions' => (string) config('maliconnect.subscriptions.payment_instructions'),
            'paymentMethods' => collect(PaymentMethod::cases())
                ->map(fn (PaymentMethod $method) => ['value' => $method->value, 'label' => $method->label()])
                ->values()
                ->all(),
            'billingCycles' => BillingCycle::options(),
            'proofMaxKb' => (int) config('maliconnect.subscriptions.proof_max_kb', 5120),
        ]);
    }

    /**
     * Affiche la comparaison des formules et permet la souscription.
     */
    public function plans(Request $request): Response
    {
        $user = $request->user();
        $ownerModels = $this->ownerModels($user);
        $owner = $this->selectedOwner($request, $ownerModels);
        $subscription = $owner !== null ? $this->subscriptionFor($owner) : null;
        $effectivePlan = $this->effectivePlan($owner);
        $currentSortOrder = $effectivePlan->sort_order;

        $plans = Plan::query()
            ->active()
            ->ordered()
            ->get()
            ->map(function (Plan $plan) use ($effectivePlan, $currentSortOrder) {
                $payload = $this->entitlements->planPayload($plan);

                return [
                    ...$payload,
                    'is_current' => (int) $plan->id === (int) $effectivePlan->id,
                    'is_upgrade' => $plan->sort_order > $currentSortOrder,
                    'is_downgrade' => $plan->sort_order < $currentSortOrder,
                ];
            })
            ->values()
            ->all();

        return Inertia::render('pro/Plans', [
            'owners' => $ownerModels->map(fn ($item) => $this->ownerPayload($item))->values()->all(),
            'selectedOwner' => $owner !== null ? $this->ownerPayload($owner) : null,
            'subscription' => $subscription !== null ? $this->subscriptionPayload($subscription) : null,
            'currentPlan' => $this->entitlements->planPayload($effectivePlan),
            'plans' => $plans,
            'paymentInstructions' => (string) config('maliconnect.subscriptions.payment_instructions'),
            'paymentMethods' => collect(PaymentMethod::cases())
                ->map(fn (PaymentMethod $method) => ['value' => $method->value, 'label' => $method->label()])
                ->values()
                ->all(),
            'billingCycles' => BillingCycle::options(),
            'proofMaxKb' => (int) config('maliconnect.subscriptions.proof_max_kb', 5120),
        ]);
    }

    /**
     * Enregistre une demande de souscription avec preuve de paiement.
     */
    public function subscribe(SubscribePlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $owner = $this->resolveOwner(
            $request->user(),
            $validated['owner_type'],
            (int) $validated['owner_id'],
        );

        $plan = Plan::query()
            ->active()
            ->whereKey($validated['plan_id'])
            ->firstOrFail();

        $hasTrial = $plan->trial_days > 0;
        $existingSubscription = $owner instanceof \App\Models\Business
            ? \App\Models\Subscription::query()->where('business_id', $owner->id)->first()
            : \App\Models\Subscription::query()->where('provider_profile_id', $owner->id)->whereNull('business_id')->first();

        // Preuve requise si : pas d'essai OU abonnement existant (changement de formule)
        $needsProof = ! $hasTrial || $existingSubscription !== null;

        if ($needsProof && ! $request->hasFile('proof')) {
            return back()->withErrors(['proof' => 'Une preuve de paiement est requise pour cette souscription.'])->withInput();
        }

        $payment = $this->subscriptions->requestPaidPlan(
            $owner,
            $plan,
            BillingCycle::from($validated['billing_cycle']),
            PaymentMethod::from($validated['method']),
            $validated['external_reference'],
            $request->file('proof'),
            $request->user(),
            $validated['notes'] ?? null,
        );

        if ($payment === null) {
            return to_route('pro.subscription.index', [
                'owner_type' => $validated['owner_type'],
                'owner_id' => $owner->id,
            ])->with('success', 'Votre essai gratuit de '.$plan->trial_days.' jours a démarré. Profitez de toutes les fonctionnalités de la formule '.$plan->name.'.');
        }

        return to_route('pro.subscription.index', [
            'owner_type' => $validated['owner_type'],
            'owner_id' => $owner->id,
        ])->with('success', 'Votre paiement a été enregistré. Il sera vérifié avant l’activation de la formule.');
    }

    /**
     * Programme le retour à la formule FREE.
     */
    public function requestFree(Request $request, Subscription $subscription): RedirectResponse
    {
        $this->authorize('cancelOwn', $subscription);

        $owner = $subscription->business ?? $subscription->providerProfile;

        abort_if($owner === null, 404);

        $this->subscriptions->requestFreePlan($owner, $request->user());

        return back()->with('success', 'Le retour à la formule FREE a été enregistré. Il prendra effet à la fin de la période payée en cours.');
    }

    /**
     * Résilie l'abonnement du propriétaire.
     */
    public function cancel(CancelSubscriptionRequest $request, Subscription $subscription): RedirectResponse
    {
        $this->authorize('cancelOwn', $subscription);

        $this->subscriptions->cancel($subscription, $request->user(), $request->validated('reason'));

        return back()->with('success', 'Votre abonnement a été résilié. La période déjà réglée reste acquise jusqu’à son terme.');
    }

    /**
     * Télécharge une preuve de paiement appartenant au propriétaire.
     */
    public function downloadProof(Payment $payment, PaymentProof $proof): StreamedResponse
    {
        $this->authorize('downloadProof', $payment);

        abort_unless($proof->payment_id === $payment->id, 404);
        abort_unless(Storage::disk($proof->disk)->exists($proof->path), 404);

        return Storage::disk($proof->disk)->download($proof->path, $proof->original_name);
    }

    // ─── Résolution du propriétaire ──────────────────────────

    /**
     * Profils indépendants et entreprises réellement pilotés par l'utilisateur.
     *
     * @return Collection<int, ProviderProfile|Business>
     */
    private function ownerModels(User $user): Collection
    {
        $profiles = ProviderProfile::query()
            ->where('user_id', $user->id)
            ->whereNull('business_id')
            ->orderBy('business_name')
            ->get();

        $businesses = $user->businessMemberships()
            ->where('status', 'active')
            ->with('business')
            ->get()
            ->filter(fn ($member) => $member->canManageProfiles() && $member->business !== null)
            ->map(fn ($member) => $member->business);

        $owned = Business::query()
            ->where('owner_id', $user->id)
            ->get();

        return $profiles
            ->concat($businesses)
            ->concat($owned)
            ->unique(fn ($owner) => $owner instanceof Business ? 'business:'.$owner->id : 'profile:'.$owner->id)
            ->values();
    }

    /**
     * Propriétaire demandé dans l'URL, ou premier propriétaire disponible.
     *
     * @param  Collection<int, ProviderProfile|Business>  $owners
     */
    private function selectedOwner(Request $request, Collection $owners): ProviderProfile|Business|null
    {
        $type = (string) $request->string('owner_type');
        $id = $request->integer('owner_id');

        if ($type !== '' && $id > 0) {
            $owner = $owners->first(fn ($item) => $this->ownerType($item) === $type && (int) $item->id === $id);

            if ($owner !== null) {
                return $owner;
            }
        }

        return $owners->first();
    }

    /**
     * Vérifie qu'un utilisateur peut agir pour le propriétaire demandé.
     */
    private function resolveOwner(User $user, string $type, int $id): ProviderProfile|Business
    {
        if ($type === 'business') {
            $business = Business::query()->findOrFail($id);

            abort_unless($user->can('manageProfiles', $business), 403);

            return $business;
        }

        $profile = ProviderProfile::query()->findOrFail($id);

        abort_unless(
            $profile->business_id === null && $profile->user_id === $user->id,
            403,
        );

        return $profile;
    }

    private function ownerType(ProviderProfile|Business $owner): string
    {
        return $owner instanceof Business ? 'business' : 'profile';
    }

    /**
     * @return array<string, mixed>
     */
    private function ownerPayload(ProviderProfile|Business $owner): array
    {
        if ($owner instanceof Business) {
            return [
                'key' => 'business:'.$owner->id,
                'type' => 'business',
                'id' => $owner->id,
                'name' => $owner->name,
                'subtitle' => 'Entreprise · '.($owner->owner?->name ?? 'Responsable inconnu'),
            ];
        }

        return [
            'key' => 'profile:'.$owner->id,
            'type' => 'profile',
            'id' => $owner->id,
            'name' => $owner->business_name,
            'subtitle' => 'Profil indépendant',
        ];
    }

    private function subscriptionFor(ProviderProfile|Business $owner): ?Subscription
    {
        return Subscription::query()
            ->forOwner(
                $owner instanceof ProviderProfile ? $owner : null,
                $owner instanceof Business ? $owner : null,
            )
            ->with([
                'planModel',
                'pendingPlan',
                'providerProfile.user:id,name,email',
                'business.owner:id,name,email',
            ])
            ->latest('id')
            ->first();
    }

    /**
     * Plan effectif : FREE dès que l'abonnement n'ouvre plus droit aux options.
     */
    private function effectivePlan(ProviderProfile|Business|null $owner): Plan
    {
        if ($owner === null) {
            return $this->entitlements->freePlan();
        }

        return $owner instanceof Business
            ? $this->entitlements->planForBusiness($owner)
            : $this->entitlements->planFor($owner);
    }

    /**
     * @return array<string, mixed>
     */
    private function subscriptionPayload(Subscription $subscription): array
    {
        return [
            'id' => $subscription->id,
            'plan' => $subscription->planModel?->only(['id', 'code', 'name']),
            'status' => $subscription->status->value,
            'status_label' => $subscription->status->label(),
            'status_tone' => $subscription->status->tone(),
            'billing_cycle' => $subscription->billing_cycle?->value,
            'billing_cycle_label' => $subscription->billingCycleLabel(),
            'amount' => (float) $subscription->amount,
            'currency' => $subscription->currency,
            'formatted_amount' => $subscription->formattedAmount(),
            'starts_at' => $subscription->starts_at?->toIso8601String(),
            'ends_at' => $subscription->ends_at?->toIso8601String(),
            'activated_at' => $subscription->activated_at?->toIso8601String(),
            'grace_ends_at' => $subscription->grace_ends_at?->toIso8601String(),
            'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
            'cancelled_at' => $subscription->cancelled_at?->toIso8601String(),
            'days_remaining' => $subscription->daysRemaining(),
            'cancel_at_period_end' => $subscription->cancel_at_period_end,
            'cancellation_reason' => $subscription->cancellation_reason,
            'is_active' => $subscription->isActive(),
            'grants_access' => $subscription->grantsAccess(),
            'has_scheduled_change' => $subscription->hasScheduledChange(),
            'pending_plan' => $subscription->pendingPlan?->only(['id', 'code', 'name']),
            'pending_plan_effective_at' => $subscription->pending_plan_effective_at?->toIso8601String(),
            'created_at' => $subscription->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function limitsForOwner(ProviderProfile|Business $owner, Plan $plan, ?Subscription $subscription): array
    {
        if ($subscription !== null && $subscription->grantsAccess()) {
            return $this->entitlements->limitsForSubscription($subscription);
        }

        if ($owner instanceof Business) {
            $profilesUsed = $owner->profiles()->count();
            $servicesUsed = Service::query()
                ->whereHas('providerProfile', fn ($query) => $query->where('business_id', $owner->id))
                ->count();
            $pricingSheetsUsed = PricingSheet::query()
                ->whereHas('providerProfile', fn ($query) => $query->where('business_id', $owner->id))
                ->count();
            $mediaUsed = ProviderProfileMedia::query()
                ->whereHas('providerProfile', fn ($query) => $query->where('business_id', $owner->id))
                ->count();

            return $this->entitlements->limitsForPlan($plan, $profilesUsed, $servicesUsed, $pricingSheetsUsed, $mediaUsed);
        }

        $user = $owner->user;

        return $this->entitlements->limitsForPlan(
            $plan,
            $user !== null ? $this->entitlements->profilesUsed($user) : 0,
            $this->entitlements->servicesUsed($owner),
            $this->entitlements->pricingSheetsUsed($owner),
            $owner->media()->count(),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function historyPayload(Subscription $subscription): array
    {
        return $subscription->histories()
            ->with(['actor:id,name', 'fromPlan:id,code,name', 'toPlan:id,code,name'])
            ->get()
            ->map(fn ($history) => [
                'id' => $history->id,
                'event' => $history->event,
                'event_label' => $history->eventLabel(),
                'from_status' => $history->from_status,
                'to_status' => $history->to_status,
                'from_plan' => $history->fromPlan?->name,
                'to_plan' => $history->toPlan?->name,
                'amount' => (float) $history->amount,
                'currency' => $history->currency,
                'note' => $history->note,
                'actor' => $history->actor?->name,
                'created_at' => $history->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function paymentsPayload(Subscription $subscription): array
    {
        return $subscription->payments()
            ->with(['plan:id,code,name', 'proofs'])
            ->latest()
            ->get()
            ->map(fn (Payment $payment) => [
                'id' => $payment->id,
                'reference' => $payment->reference,
                'external_reference' => $payment->external_reference,
                'plan' => $payment->plan?->name,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'formatted_amount' => $payment->formattedAmount(),
                'method' => $payment->method->value,
                'method_label' => $payment->method->label(),
                'billing_cycle' => $payment->billing_cycle?->value,
                'billing_cycle_label' => $payment->billing_cycle?->label(),
                'status' => $payment->status->value,
                'status_label' => $payment->status->label(),
                'status_tone' => $payment->status->tone(),
                'rejection_reason' => $payment->rejection_reason,
                'declared_at' => $payment->declared_at?->toIso8601String(),
                'paid_at' => $payment->paid_at?->toIso8601String(),
                'created_at' => $payment->created_at?->toIso8601String(),
                'proofs' => $payment->proofs->map(fn (PaymentProof $proof) => [
                    'id' => $proof->id,
                    'original_name' => $proof->original_name,
                    'formatted_size' => $proof->formattedSize(),
                    'download_url' => route('pro.subscription.proofs.download', [$payment, $proof]),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }
}
