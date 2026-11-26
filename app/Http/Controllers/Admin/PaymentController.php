<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApprovePaymentRequest;
use App\Http\Requests\Admin\RejectPaymentRequest;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\Plan;
use App\Services\Admin\AuditLogger;
use App\Services\Monetization\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Supervision administrative des paiements.
 *
 * Seuls les paiements au statut « paid » alimentent les revenus. La
 * validation et le refus passent par SubscriptionService, qui verrouille le
 * dossier et interdit toute double activation.
 */
class PaymentController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    /**
     * Affiche la liste filtrable des paiements.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Payment::class);

        $search = trim((string) $request->string('search'));
        $status = (string) $request->string('status');
        $method = (string) $request->string('method');
        $plan = (string) $request->string('plan');

        $query = Payment::query()
            ->with([
                'user:id,name,email',
                'providerProfile:id,business_name,slug',
                'business:id,name,slug',
                'plan:id,code,name',
            ])
            ->withCount('proofs')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('reference', 'like', '%'.$search.'%')
                        ->orWhere('external_reference', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'))
                        ->orWhereHas('providerProfile', fn ($profile) => $profile->where('business_name', 'like', '%'.$search.'%'))
                        ->orWhereHas('business', fn ($business) => $business->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($method !== '', fn ($query) => $query->where('method', $method))
            ->when($plan !== '', fn ($query) => $query->whereHas('plan', fn ($item) => $item->where('code', $plan)));

        $paidTotal = (float) (clone $query)->paid()->sum('amount');
        $pendingAmount = (float) (clone $query)->pending()->sum('amount');
        $pendingCount = (clone $query)->pending()->count();

        $paginator = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('admin/Payments', [
            'payments' => [
                'data' => collect($paginator->items())
                    ->map(fn (Payment $payment) => $this->summarize($payment))
                    ->all(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
                'method' => $method,
                'plan' => $plan,
            ],
            'statuses' => collect(PaymentStatus::cases())
                ->map(fn (PaymentStatus $item) => ['value' => $item->value, 'label' => $item->label()])
                ->values()
                ->all(),
            'methods' => collect(PaymentMethod::cases())
                ->map(fn (PaymentMethod $item) => ['value' => $item->value, 'label' => $item->label()])
                ->values()
                ->all(),
            'plans' => Plan::query()
                ->ordered()
                ->get(['id', 'code', 'name'])
                ->map(fn (Plan $item) => ['value' => $item->code, 'label' => $item->name])
                ->values()
                ->all(),
            'stats' => [
                'paid_total' => $paidTotal,
                'pending_count' => $pendingCount,
                'pending_amount' => $pendingAmount,
            ],
        ]);
    }

    /**
     * Affiche le détail d'un paiement et de ses preuves.
     */
    public function show(Payment $payment): Response
    {
        $this->authorize('view', $payment);

        $payment->load([
            'user:id,name,email',
            'providerProfile:id,business_name,slug',
            'business:id,name,slug',
            'plan:id,code,name',
            'subscription:id,plan_id,plan,status,starts_at,ends_at,amount,currency',
            'subscription.planModel:id,code,name',
            'proofs' => fn ($query) => $query->latest(),
            'verifier:id,name',
        ]);

        return Inertia::render('admin/PaymentDetail', [
            'payment' => [
                ...$this->summarize($payment),
                'notes' => $payment->notes,
                'rejection_reason' => $payment->rejection_reason,
                'declared_at' => $payment->declared_at?->toIso8601String(),
                'paid_at' => $payment->paid_at?->toIso8601String(),
                'verified_at' => $payment->verified_at?->toIso8601String(),
                'verified_by' => $payment->verifier?->name,
                'user' => $payment->user?->only(['id', 'name', 'email']),
                'owner' => $this->ownerPayload($payment),
                'plan' => $payment->plan?->only(['id', 'code', 'name']),
                'subscription' => $payment->subscription !== null ? [
                    'id' => $payment->subscription->id,
                    'plan' => $payment->subscription->planModel?->only(['id', 'code', 'name']),
                    'status' => $payment->subscription->status->value,
                    'status_label' => $payment->subscription->status->label(),
                    'starts_at' => $payment->subscription->starts_at?->toIso8601String(),
                    'ends_at' => $payment->subscription->ends_at?->toIso8601String(),
                    'url' => route('admin.subscriptions.show', $payment->subscription),
                ] : null,
                'proofs' => $payment->proofs->map(fn (PaymentProof $proof) => [
                    'id' => $proof->id,
                    'original_name' => $proof->original_name,
                    'mime_type' => $proof->mime_type,
                    'size' => $proof->size,
                    'formatted_size' => $proof->formattedSize(),
                    'created_at' => $proof->created_at?->toIso8601String(),
                    'download_url' => route('admin.payments.proofs.download', [$payment, $proof]),
                ])->values()->all(),
            ],
        ]);
    }

    /**
     * Valide un paiement après vérification de la preuve.
     */
    public function approve(
        ApprovePaymentRequest $request,
        Payment $payment,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->authorize('manage', $payment);

        $subscription = $this->subscriptions->approvePayment(
            $payment,
            $request->user(),
            $request->validated('note'),
        );

        $payment->refresh();

        $audit->log(
            'payment.approved',
            sprintf(
                'Paiement %s de « %s » validé pour un montant de %s.',
                $payment->reference,
                $payment->ownerLabel(),
                $payment->formattedAmount(),
            ),
            $payment,
            [
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'subscription_id' => $subscription->id,
                'subscription_ends_at' => $subscription->ends_at?->toIso8601String(),
                'note' => $request->validated('note'),
            ],
            $request->user(),
            $request,
        );

        return back()->with('success', 'Le paiement a été validé et l’abonnement activé.');
    }

    /**
     * Refuse un paiement avec une justification obligatoire.
     */
    public function reject(
        RejectPaymentRequest $request,
        Payment $payment,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->authorize('manage', $payment);

        $this->subscriptions->rejectPayment(
            $payment,
            $request->user(),
            $request->validated('rejection_reason'),
        );

        $payment->refresh();

        $audit->log(
            'payment.rejected',
            sprintf(
                'Paiement %s de « %s » refusé.',
                $payment->reference,
                $payment->ownerLabel(),
            ),
            $payment,
            [
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'rejection_reason' => $request->validated('rejection_reason'),
            ],
            $request->user(),
            $request,
        );

        return back()->with('success', 'Le paiement a été refusé.');
    }

    /**
     * Télécharge une preuve stockée sur le disque privé.
     */
    public function downloadProof(Payment $payment, PaymentProof $proof): StreamedResponse
    {
        $this->authorize('downloadProof', $payment);

        abort_unless($proof->payment_id === $payment->id, 404);
        abort_if(str_contains($proof->path, '..'), 404);
        abort_unless(Storage::disk($proof->disk)->exists($proof->path), 404);

        return Storage::disk($proof->disk)->download($proof->path, $proof->original_name);
    }

    /**
     * Résume un paiement pour l'interface d'administration.
     *
     * @return array<string, mixed>
     */
    private function summarize(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'reference' => $payment->reference,
            'external_reference' => $payment->external_reference,
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
            'plan' => $payment->plan?->only(['id', 'code', 'name']),
            'owner' => $this->ownerPayload($payment),
            'owner_label' => $payment->ownerLabel(),
            'user' => $payment->user?->name,
            'professional' => $payment->providerProfile?->business_name,
            'business' => $payment->business?->name,
            'subscription_id' => $payment->subscription_id,
            'proofs_count' => $payment->proofs_count ?? $payment->proofs()->count(),
            'can_review' => $payment->isPending() && (auth()->user()?->can('payments.manage') ?? false),
            'declared_at' => $payment->declared_at?->toIso8601String(),
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'verified_at' => $payment->verified_at?->toIso8601String(),
            'created_at' => $payment->created_at?->toIso8601String(),
        ];
    }

    /**
     * Propriétaire concerné par le paiement.
     *
     * @return array<string, mixed>
     */
    private function ownerPayload(Payment $payment): array
    {
        if ($payment->business !== null) {
            return [
                'type' => 'business',
                'id' => $payment->business->id,
                'name' => $payment->business->name,
                'slug' => $payment->business->slug,
            ];
        }

        if ($payment->providerProfile !== null) {
            return [
                'type' => 'profile',
                'id' => $payment->providerProfile->id,
                'name' => $payment->providerProfile->business_name,
                'slug' => $payment->providerProfile->slug,
            ];
        }

        return [
            'type' => 'account',
            'id' => $payment->user?->id,
            'name' => $payment->user?->name ?? 'Compte inconnu',
            'slug' => null,
        ];
    }
}
