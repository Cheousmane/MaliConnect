<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelSubscriptionRequest;
use App\Http\Requests\Admin\ExtendSubscriptionRequest;
use App\Http\Requests\Admin\SuspendSubscriptionRequest;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Admin\AuditLogger;
use App\Services\Monetization\EntitlementService;
use App\Services\Monetization\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Supervision administrative des abonnements professionnels.
 *
 * Les transitions du cycle de vie passent exclusivement par
 * SubscriptionService : aucune activation ne peut être obtenue par un simple
 * changement de statut. Chaque action est journalisée.
 */
class SubscriptionController extends Controller
{
    public function __construct(
        private readonly EntitlementService $entitlements,
        private readonly SubscriptionService $subscriptions,
    ) {}

    /**
     * Affiche la liste filtrable des abonnements.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Subscription::class);

        $search = trim((string) $request->string('search'));
        $status = (string) $request->string('status');
        $plan = (string) $request->string('plan');
        $ownerType = (string) $request->string('owner_type');

        $paginator = Subscription::query()
            ->with([
                'providerProfile:id,business_name,slug',
                'providerProfile.user:id,name',
                'business:id,name,slug',
                'planModel:id,code,name',
                'pendingPlan:id,code,name',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->whereHas('providerProfile', function ($profile) use ($search) {
                        $profile->where('business_name', 'like', '%'.$search.'%')
                            ->orWhereHas('user', fn ($user) => $user->where('name', 'like', '%'.$search.'%'));
                    })->orWhereHas('business', fn ($business) => $business->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($plan !== '', function ($query) use ($plan) {
                $query->where(function ($sub) use ($plan) {
                    $sub->where('plan', $plan)
                        ->orWhereHas('planModel', fn ($model) => $model->where('code', $plan));
                });
            })
            ->when($ownerType === 'business', fn ($query) => $query->whereNotNull('business_id'))
            ->when($ownerType === 'profile', fn ($query) => $query->whereNull('business_id'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/Subscriptions', [
            'subscriptions' => [
                'data' => collect($paginator->items())
                    ->map(fn (Subscription $subscription) => $this->summarize($subscription))
                    ->all(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
                'plan' => $plan,
                'owner_type' => $ownerType,
            ],
            'statuses' => $this->statuses(),
            'plans' => Plan::query()
                ->ordered()
                ->get(['id', 'code', 'name'])
                ->map(fn (Plan $item) => [
                    'value' => $item->code,
                    'label' => $item->name,
                ])
                ->values()
                ->all(),
            'stats' => [
                'active' => Subscription::query()->active()->count(),
                'pending' => Subscription::query()->where('status', SubscriptionStatus::Pending)->count(),
                'past_due' => Subscription::query()->where('status', SubscriptionStatus::PastDue)->count(),
                'expired' => Subscription::query()->where('status', SubscriptionStatus::Expired)->count(),
                'suspended' => Subscription::query()->where('status', SubscriptionStatus::Suspended)->count(),
            ],
        ]);
    }

    /**
     * Affiche le détail complet d'un abonnement.
     */
    public function show(Subscription $subscription): Response
    {
        $this->authorize('view', $subscription);

        $subscription->load([
            'providerProfile:id,business_name,slug,user_id',
            'providerProfile.user:id,name,email',
            'business:id,name,slug,owner_id',
            'business.owner:id,name,email',
            'planModel',
            'pendingPlan',
            'payments' => fn ($query) => $query->withCount('proofs')->latest(),
            'histories' => fn ($query) => $query->with(['actor:id,name', 'fromPlan:id,code,name', 'toPlan:id,code,name']),
        ]);

        $plan = $this->entitlements->planFromSubscription($subscription);

        return Inertia::render('admin/SubscriptionDetail', [
            'subscription' => [
                ...$this->summarize($subscription),
                'starts_at' => $subscription->starts_at?->toIso8601String(),
                'ends_at' => $subscription->ends_at?->toIso8601String(),
                'activated_at' => $subscription->activated_at?->toIso8601String(),
                'cancelled_at' => $subscription->cancelled_at?->toIso8601String(),
                'suspended_at' => $subscription->suspended_at?->toIso8601String(),
                'grace_ends_at' => $subscription->grace_ends_at?->toIso8601String(),
                'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                'renewed_at' => $subscription->renewed_at?->toIso8601String(),
                'last_payment_at' => $subscription->last_payment_at?->toIso8601String(),
                'cancel_at_period_end' => $subscription->cancel_at_period_end,
                'cancellation_reason' => $subscription->cancellation_reason,
                'notes' => $subscription->notes,
                'days_remaining' => $subscription->daysRemaining(),
                'plan' => $plan->only(['id', 'code', 'name', 'price_monthly', 'price_yearly', 'currency']),
                'features' => $this->entitlements->featuresForPlan($plan),
                'limits' => $this->entitlements->limitsForSubscription($subscription),
                'payments' => $subscription->payments->map(fn ($payment) => [
                    'id' => $payment->id,
                    'reference' => $payment->reference,
                    'external_reference' => $payment->external_reference,
                    'amount' => (float) $payment->amount,
                    'currency' => $payment->currency,
                    'formatted_amount' => $payment->formattedAmount(),
                    'method' => $payment->method->value,
                    'method_label' => $payment->method->label(),
                    'status' => $payment->status->value,
                    'status_label' => $payment->status->label(),
                    'status_tone' => $payment->status->tone(),
                    'proofs_count' => $payment->proofs_count,
                    'declared_at' => $payment->declared_at?->toIso8601String(),
                    'paid_at' => $payment->paid_at?->toIso8601String(),
                    'url' => route('admin.payments.show', $payment),
                ])->values()->all(),
                'histories' => $subscription->histories->map(fn ($history) => [
                    'id' => $history->id,
                    'event' => $history->event,
                    'event_label' => $history->eventLabel(),
                    'from_status' => $history->from_status,
                    'from_status_label' => SubscriptionStatus::tryFrom((string) $history->from_status)?->label(),
                    'to_status' => $history->to_status,
                    'to_status_label' => SubscriptionStatus::tryFrom((string) $history->to_status)?->label(),
                    'from_plan' => $history->fromPlan?->name,
                    'to_plan' => $history->toPlan?->name,
                    'billing_cycle' => $history->billing_cycle,
                    'amount' => (float) $history->amount,
                    'currency' => $history->currency,
                    'note' => $history->note,
                    'actor' => $history->actor?->name,
                    'created_at' => $history->created_at?->toIso8601String(),
                ])->values()->all(),
            ],
        ]);
    }

    /**
     * Suspend un abonnement sans supprimer les données du professionnel.
     */
    public function suspend(
        SuspendSubscriptionRequest $request,
        Subscription $subscription,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->authorize('manage', $subscription);

        $previous = $subscription->status;

        $this->subscriptions->suspend($subscription, $request->user(), $request->validated('reason'));
        $subscription->refresh();

        $audit->log(
            'subscription.suspended',
            sprintf(
                'Abonnement #%d de « %s » suspendu (%s → %s).',
                $subscription->id,
                $subscription->ownerLabel(),
                $previous->label(),
                $subscription->status->label(),
            ),
            $subscription,
            [
                'previous_status' => $previous->value,
                'new_status' => $subscription->status->value,
                'reason' => $request->validated('reason'),
            ],
            $request->user(),
            $request,
        );

        return back()->with('success', 'L’abonnement a été suspendu.');
    }

    /**
     * Réactive un abonnement suspendu ou expiré.
     */
    public function reactivate(
        Request $request,
        Subscription $subscription,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->authorize('manage', $subscription);

        $previous = $subscription->status;

        $this->subscriptions->reactivate($subscription, $request->user());
        $subscription->refresh();

        $audit->log(
            'subscription.reactivated',
            sprintf(
                'Abonnement #%d de « %s » réactivé (%s → %s).',
                $subscription->id,
                $subscription->ownerLabel(),
                $previous->label(),
                $subscription->status->label(),
            ),
            $subscription,
            [
                'previous_status' => $previous->value,
                'new_status' => $subscription->status->value,
                'ends_at' => $subscription->ends_at?->toIso8601String(),
            ],
            $request->user(),
            $request,
        );

        return back()->with('success', 'L’abonnement a été réactivé.');
    }

    /**
     * Résilie un abonnement.
     *
     * Une période déjà réglée reste acquise jusqu'à son terme.
     */
    public function cancel(
        CancelSubscriptionRequest $request,
        Subscription $subscription,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->authorize('manage', $subscription);

        $previous = $subscription->status;

        $this->subscriptions->cancel($subscription, $request->user(), $request->validated('reason'));
        $subscription->refresh();

        $audit->log(
            'subscription.cancelled',
            sprintf(
                'Abonnement #%d de « %s » résilié (%s → %s).',
                $subscription->id,
                $subscription->ownerLabel(),
                $previous->label(),
                $subscription->status->label(),
            ),
            $subscription,
            [
                'previous_status' => $previous->value,
                'new_status' => $subscription->status->value,
                'cancel_at_period_end' => $subscription->cancel_at_period_end,
                'reason' => $request->validated('reason'),
            ],
            $request->user(),
            $request,
        );

        return back()->with('success', 'L’abonnement a été résilié.');
    }

    /**
     * Prolonge l'échéance d'un abonnement.
     */
    public function extend(
        ExtendSubscriptionRequest $request,
        Subscription $subscription,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->authorize('manage', $subscription);

        $previousStatus = $subscription->status;
        $previousEndsAt = $subscription->ends_at;
        $endsAt = Carbon::parse($request->validated('ends_at'));

        $this->subscriptions->extend($subscription, $endsAt, $request->user(), $request->validated('note'));
        $subscription->refresh();

        $audit->log(
            'subscription.extended',
            sprintf(
                'Abonnement #%d de « %s » prolongé jusqu’au %s.',
                $subscription->id,
                $subscription->ownerLabel(),
                $endsAt->format('d/m/Y H:i'),
            ),
            $subscription,
            [
                'previous_status' => $previousStatus->value,
                'previous_ends_at' => $previousEndsAt?->toIso8601String(),
                'new_ends_at' => $subscription->ends_at?->toIso8601String(),
                'note' => $request->validated('note'),
            ],
            $request->user(),
            $request,
        );

        return back()->with('success', 'L’échéance de l’abonnement a été prolongée.');
    }

    /**
     * Résume un abonnement pour l'interface d'administration.
     *
     * @return array<string, mixed>
     */
    private function summarize(Subscription $subscription): array
    {
        $plan = $subscription->planModel;
        $pendingPlan = $subscription->pendingPlan;

        return [
            'id' => $subscription->id,
            'plan' => $plan?->only(['id', 'code', 'name']) ?? [
                'id' => null,
                'code' => $subscription->plan,
                'name' => strtoupper((string) $subscription->plan),
            ],
            'plan_code' => $plan?->code ?? $subscription->plan,
            'plan_name' => $plan?->name ?? strtoupper((string) $subscription->plan),
            'pending_plan' => $pendingPlan?->only(['id', 'code', 'name']),
            'pending_plan_effective_at' => $subscription->pending_plan_effective_at?->toIso8601String(),
            'has_scheduled_change' => $subscription->hasScheduledChange(),
            'status' => $subscription->status->value,
            'status_label' => $subscription->status->label(),
            'status_tone' => $subscription->status->tone(),
            'billing_cycle' => $subscription->billing_cycle?->value,
            'billing_cycle_label' => $subscription->billingCycleLabel(),
            'amount' => (float) $subscription->amount,
            'currency' => $subscription->currency,
            'formatted_amount' => $subscription->formattedAmount(),
            'is_active' => $subscription->isActive(),
            'grants_access' => $subscription->grantsAccess(),
            'owner' => $this->ownerPayload($subscription),
            'owner_label' => $subscription->ownerLabel(),
            'owner_type' => $subscription->ownerType(),
            'starts_at' => $subscription->starts_at?->toIso8601String(),
            'ends_at' => $subscription->ends_at?->toIso8601String(),
            'created_at' => $subscription->created_at?->toIso8601String(),
        ];
    }

    /**
     * Propriétaire affiché dans les listes.
     *
     * @return array<string, mixed>
     */
    private function ownerPayload(Subscription $subscription): array
    {
        if ($subscription->business !== null) {
            return [
                'type' => 'business',
                'id' => $subscription->business->id,
                'name' => $subscription->business->name,
                'slug' => $subscription->business->slug,
                'manager' => $subscription->business->owner?->name,
                'email' => $subscription->business->owner?->email,
            ];
        }

        return [
            'type' => 'profile',
            'id' => $subscription->providerProfile?->id,
            'name' => $subscription->providerProfile?->business_name ?? 'Profil inconnu',
            'slug' => $subscription->providerProfile?->slug,
            'manager' => $subscription->providerProfile?->user?->name,
            'email' => $subscription->providerProfile?->user?->email,
        ];
    }

    /**
     * Liste des statuts disponibles.
     *
     * @return list<array{value: string, label: string}>
     */
    private function statuses(): array
    {
        return collect(SubscriptionStatus::cases())
            ->map(fn (SubscriptionStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->values()
            ->all();
    }
}
