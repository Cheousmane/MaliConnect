<?php

namespace App\Services\Monetization;

use App\Enums\BillingCycle;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\Plan;
use App\Models\ProviderProfile;
use App\Models\Subscription;
use App\Models\SubscriptionHistory;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Orchestre le cycle de vie complet des abonnements.
 *
 * Toutes les opérations critiques s'exécutent dans une transaction et
 * verrouillent la ligne concernée. Une validation de paiement déjà traitée
 * ne peut jamais être rejouée.
 */
final class SubscriptionService
{
    public function __construct(
        private readonly EntitlementService $entitlements,
        private readonly SubscriptionNotifier $notifier,
    ) {}

    /**
     * Crée un abonnement FREE pour un propriétaire s'il n'en a pas.
     *
     * Permet d'afficher tous les comptes (y compris FREE) dans
     * l'administration des abonnements.
     */
    public function ensureFreeSubscription(ProviderProfile|Business $owner, User $actor): ?Subscription
    {
        [$profile, $business] = $this->ownerIds($owner);

        $existing = Subscription::query()
            ->forOwner($profile, $business)
            ->first();

        if ($existing !== null) {
            return null;
        }

        $freePlan = $this->entitlements->freePlan();

        return DB::transaction(function () use ($profile, $business, $freePlan, $actor) {
            $subscription = Subscription::create([
                'provider_profile_id' => $profile?->id,
                'business_id' => $business?->id,
                'plan_id' => $freePlan->id,
                'plan' => $freePlan->code,
                'billing_cycle' => \App\Enums\BillingCycle::Monthly,
                'status' => SubscriptionStatus::Active,
                'starts_at' => now(),
                'ends_at' => null,
                'activated_at' => now(),
                'amount' => 0,
                'currency' => $freePlan->currency ?: 'XOF',
            ]);

            $this->recordHistory(
                $subscription,
                'created',
                null,
                SubscriptionStatus::Active,
                null,
                $freePlan,
                \App\Enums\BillingCycle::Monthly,
                0,
                $actor,
                'Abonnement FREE créé automatiquement.',
            );

            return $subscription;
        });
    }

    // ─── Souscription ────────────────────────────────────────

    /**
     * Déclare un paiement pour une formule payante.
     *
     * Un abonnement en attente est créé si le propriétaire n'en a pas encore.
     * Pour un abonnement actif, le changement de formule est mémorisé et ne
     * sera appliqué qu'après vérification du paiement.
     */
    public function requestPaidPlan(
        ProviderProfile|Business $owner,
        Plan $plan,
        BillingCycle $cycle,
        PaymentMethod $method,
        string $externalReference,
        ?UploadedFile $proof,
        User $actor,
        ?string $notes = null,
    ): ?Payment {
        if (! $plan->is_active) {
            throw ValidationException::withMessages([
                'plan_id' => 'Cette formule n’est plus disponible.',
            ]);
        }

        if ($plan->isFree()) {
            throw ValidationException::withMessages([
                'plan_id' => 'La formule FREE ne nécessite aucun paiement.',
            ]);
        }

        [$profile, $business] = $this->ownerIds($owner);

        // Si le plan offre un essai gratuit et qu'il n'y a pas encore d'abonnement,
        // on crée un abonnement en période d'essai sans paiement.
        $hasTrial = $plan->trial_days > 0;

        $existingSubscription = Subscription::query()
            ->forOwner($profile, $business)
            ->first();

        if ($hasTrial && $existingSubscription === null) {
            $subscription = DB::transaction(function () use ($profile, $business, $plan, $cycle, $actor) {
                $subscription = Subscription::create([
                    'provider_profile_id' => $profile?->id,
                    'business_id' => $business?->id,
                    'plan_id' => $plan->id,
                    'plan' => $plan->code,
                    'billing_cycle' => $cycle,
                    'status' => SubscriptionStatus::Trialing,
                    'starts_at' => now(),
                    'ends_at' => null,
                    'activated_at' => null,
                    'trial_ends_at' => now()->addDays($plan->trial_days),
                    'amount' => $plan->priceFor($cycle),
                    'currency' => $plan->currency ?: 'XOF',
                ]);

                $this->recordHistory(
                    $subscription,
                    'trial_started',
                    null,
                    SubscriptionStatus::Trialing,
                    null,
                    $plan,
                    $cycle,
                    $plan->priceFor($cycle),
                    $actor,
                    'Essai gratuit de '.$plan->trial_days.' jours démarré pour la formule '.$plan->name.'.',
                );

                return $subscription;
            });

            $this->notifier->subscriptionActivated($subscription);

            return null;
        }

        $payment = DB::transaction(function () use ($profile, $business, $plan, $cycle, $method, $externalReference, $proof, $actor, $notes) {
            $subscription = Subscription::query()
                ->forOwner($profile, $business)
                ->lockForUpdate()
                ->first();

            $hasPendingPayment = Payment::query()
                ->where('status', PaymentStatus::Pending)
                ->when($subscription !== null, fn ($query) => $query->where('subscription_id', $subscription->id))
                ->when($subscription === null, function ($query) use ($profile, $business) {
                    $query->where(function ($sub) use ($profile, $business) {
                        if ($business !== null) {
                            $sub->where('business_id', $business->id);
                        } else {
                            $sub->where('provider_profile_id', $profile?->id)->whereNull('business_id');
                        }
                    });
                })
                ->lockForUpdate()
                ->exists();

            if ($hasPendingPayment) {
                throw ValidationException::withMessages([
                    'payment' => 'Un paiement est déjà en attente de vérification pour ce propriétaire.',
                ]);
            }

            $previousStatus = $subscription?->status;
            $previousPlan = $subscription?->planModel;

            if ($subscription === null) {
                $subscription = Subscription::create([
                    'provider_profile_id' => $profile?->id,
                    'business_id' => $business?->id,
                    'plan_id' => $plan->id,
                    'plan' => $plan->code,
                    'billing_cycle' => $cycle,
                    'status' => SubscriptionStatus::Pending,
                    'amount' => $plan->priceFor($cycle),
                    'currency' => $plan->currency ?: 'XOF',
                ]);

                $this->recordHistory($subscription, 'created', null, SubscriptionStatus::Pending, null, $plan, $cycle, $plan->priceFor($cycle), $actor, 'Demande de souscription à la formule '.$plan->name.'.');
            } elseif ($subscription->status === SubscriptionStatus::Pending) {
                $subscription->forceFill([
                    'plan_id' => $plan->id,
                    'plan' => $plan->code,
                    'billing_cycle' => $cycle,
                    'amount' => $plan->priceFor($cycle),
                    'currency' => $plan->currency ?: 'XOF',
                ])->save();

                $this->recordHistory($subscription, 'plan_changed', SubscriptionStatus::Pending, SubscriptionStatus::Pending, $previousPlan, $plan, $cycle, $plan->priceFor($cycle), $actor, 'Formule de la demande en attente mise à jour.');
            } elseif ($subscription->status->isTerminal()) {
                $subscription->forceFill([
                    'plan_id' => $plan->id,
                    'plan' => $plan->code,
                    'billing_cycle' => $cycle,
                    'status' => SubscriptionStatus::Pending,
                    'starts_at' => null,
                    'ends_at' => null,
                    'activated_at' => null,
                    'cancelled_at' => null,
                    'cancel_at_period_end' => false,
                    'cancellation_reason' => null,
                    'suspended_at' => null,
                    'grace_ends_at' => null,
                    'trial_ends_at' => null,
                    'pending_plan_id' => null,
                    'pending_billing_cycle' => null,
                    'pending_plan_effective_at' => null,
                    'amount' => $plan->priceFor($cycle),
                    'currency' => $plan->currency ?: 'XOF',
                ])->save();

                $this->recordHistory($subscription, 'created', $previousStatus, SubscriptionStatus::Pending, $previousPlan, $plan, $cycle, $plan->priceFor($cycle), $actor, 'Nouvelle demande de souscription après une période terminée.');
            } else {
                $subscription->forceFill([
                    'pending_plan_id' => $plan->id,
                    'pending_billing_cycle' => $cycle,
                    'pending_plan_effective_at' => null,
                ])->save();

                $this->recordHistory($subscription, 'plan_scheduled', $subscription->status, $subscription->status, $previousPlan, $plan, $cycle, $plan->priceFor($cycle), $actor, 'Changement de formule en attente de vérification du paiement.');
            }

            $payment = Payment::create([
                'subscription_id' => $subscription->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $cycle,
                'user_id' => $actor->id,
                'provider_profile_id' => $profile?->id,
                'business_id' => $business?->id,
                'amount' => $plan->priceFor($cycle),
                'currency' => $plan->currency ?: 'XOF',
                'method' => $method,
                'gateway' => config('maliconnect.subscriptions.default_gateway', 'manual'),
                'status' => PaymentStatus::Pending,
                'external_reference' => $externalReference,
                'notes' => $notes,
                'declared_at' => now(),
            ]);

            if ($proof === null) {
                throw ValidationException::withMessages([
                    'proof' => 'Une preuve de paiement est requise pour cette souscription.',
                ]);
            }

            $disk = (string) config('maliconnect.subscriptions.proof_disk', 'local');
            $path = $proof->store('payments/'.$payment->id.'/proofs', $disk);

            PaymentProof::create([
                'payment_id' => $payment->id,
                'uploaded_by' => $actor->id,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $proof->getClientOriginalName(),
                'mime_type' => $proof->getClientMimeType(),
                'size' => $proof->getSize(),
            ]);

            return $payment;
        });

        $this->notifier->paymentReceived($payment);

        return $payment;
    }

    /**
     * Programme le retour à la formule FREE.
     *
     * Un abonnement payant reste actif jusqu'à la fin de la période déjà
     * réglée. Sans période payée en cours, la demande en attente est annulée.
     */
    public function requestFreePlan(ProviderProfile|Business $owner, User $actor): ?Subscription
    {
        [$profile, $business] = $this->ownerIds($owner);

        $result = DB::transaction(function () use ($profile, $business, $actor) {
            $subscription = Subscription::query()
                ->forOwner($profile, $business)
                ->lockForUpdate()
                ->first();

            if ($subscription === null) {
                return null;
            }

            $freePlan = $this->entitlements->freePlan();

            if ((int) $subscription->plan_id === (int) $freePlan->id) {
                return null;
            }

            $previousPlan = $subscription->planModel;

            if ($subscription->grantsAccess() && $subscription->ends_at !== null && $subscription->ends_at->isFuture()) {
                $subscription->forceFill([
                    'pending_plan_id' => $freePlan->id,
                    'pending_billing_cycle' => BillingCycle::Monthly,
                    'pending_plan_effective_at' => $subscription->ends_at,
                    'cancel_at_period_end' => true,
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Passage à la formule FREE demandé par le professionnel.',
                ])->save();

                $this->recordHistory($subscription, 'plan_scheduled', $subscription->status, $subscription->status, $previousPlan, $freePlan, BillingCycle::Monthly, 0, $actor, 'Passage à FREE programmé à la fin de la période payée.');

                return $subscription;
            }

            // Aucune période payée en cours : la demande en attente est annulée.
            Payment::query()
                ->where('subscription_id', $subscription->id)
                ->where('status', PaymentStatus::Pending)
                ->update([
                    'status' => PaymentStatus::Failed,
                    'notes' => 'Demande annulée par le professionnel au profit de la formule FREE.',
                    'verified_at' => now(),
                ]);

            $previousStatus = $subscription->status;

            $subscription->forceFill([
                'plan_id' => $freePlan->id,
                'plan' => $freePlan->code,
                'billing_cycle' => BillingCycle::Monthly,
                'status' => SubscriptionStatus::Cancelled,
                'starts_at' => null,
                'ends_at' => now(),
                'cancelled_at' => now(),
                'cancellation_reason' => 'Passage à la formule FREE.',
                'pending_plan_id' => null,
                'pending_billing_cycle' => null,
                'pending_plan_effective_at' => null,
                'amount' => 0,
            ])->save();

            $this->recordHistory($subscription, 'cancelled', $previousStatus, SubscriptionStatus::Cancelled, $previousPlan, $freePlan, BillingCycle::Monthly, 0, $actor, 'Passage immédiat à la formule FREE.');

            return $subscription;
        });

        return $result;
    }

    // ─── Validation des paiements ────────────────────────────

    /**
     * Accepte un paiement et active ou renouvelle l'abonnement.
     *
     * L'opération est idempotente : un paiement déjà payé ou refusé ne peut
     * pas être validé une seconde fois.
     */
    public function approvePayment(Payment $payment, User $admin, ?string $note = null): Subscription
    {
        [$subscription] = DB::transaction(function () use ($payment, $admin, $note) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if (! $payment->isPending()) {
                throw ValidationException::withMessages([
                    'payment' => 'Ce paiement a déjà été traité et ne peut plus être validé.',
                ]);
            }

            // Une capture ou un reçu doit exister pour que l'administration
            // puisse vérifier la transaction avant toute activation.
            if (! $payment->proofs()->exists()) {
                throw ValidationException::withMessages([
                    'payment' => 'Aucune preuve de paiement n’est joint à ce dossier : la validation est impossible.',
                ]);
            }

            if ((float) $payment->amount <= 0) {
                throw ValidationException::withMessages([
                    'payment' => 'Le montant du paiement doit être strictement positif.',
                ]);
            }

            $payment->forceFill([
                'status' => PaymentStatus::Paid,
                'paid_at' => $payment->paid_at ?? now(),
                'verified_at' => now(),
                'verified_by' => $admin->id,
                'notes' => $this->appendNote($payment->notes, $note),
            ])->save();

            $subscription = $this->resolveSubscriptionForPayment($payment);

            if ($subscription === null) {
                throw ValidationException::withMessages([
                    'payment' => 'Le paiement n’est rattaché à aucun propriétaire professionnel.',
                ]);
            }

            $subscription = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);

            $previousStatus = $subscription->status;
            $previousPlan = $subscription->planModel;
            $targetPlan = $payment->plan_id !== null
                ? Plan::query()->find($payment->plan_id)
                : $previousPlan;
            $cycle = $payment->billing_cycle ?? $subscription->billing_cycle ?? BillingCycle::Monthly;

            if ($targetPlan === null) {
                throw ValidationException::withMessages([
                    'payment' => 'La formule visée par le paiement est introuvable.',
                ]);
            }

            $isNewPeriod = $subscription->status === SubscriptionStatus::Pending
                || $subscription->status->isTerminal();

            if ($isNewPeriod) {
                $startsAt = now();

                $subscription->forceFill([
                    'plan_id' => $targetPlan->id,
                    'plan' => $targetPlan->code,
                    'billing_cycle' => $cycle,
                    'status' => SubscriptionStatus::Active,
                    'starts_at' => $startsAt,
                    'ends_at' => $cycle->addTo($startsAt),
                    'activated_at' => $startsAt,
                    'last_payment_at' => now(),
                    'grace_ends_at' => null,
                    'trial_ends_at' => null,
                    'suspended_at' => null,
                    'cancelled_at' => null,
                    'cancel_at_period_end' => false,
                    'cancellation_reason' => null,
                    'pending_plan_id' => null,
                    'pending_billing_cycle' => null,
                    'pending_plan_effective_at' => null,
                    'past_due_notified_at' => null,
                    'expiry_reminder_sent_at' => null,
                    'amount' => (float) $payment->amount,
                    'currency' => $payment->currency ?: $subscription->currency,
                ])->save();

                $event = 'activated';
            } elseif ((int) $subscription->plan_id === (int) $targetPlan->id) {
                $base = $subscription->ends_at !== null && $subscription->ends_at->isFuture()
                    ? $subscription->ends_at
                    : now();

                $subscription->forceFill([
                    'billing_cycle' => $cycle,
                    'status' => SubscriptionStatus::Active,
                    'ends_at' => $cycle->addTo($base),
                    'renewed_at' => now(),
                    'last_payment_at' => now(),
                    'grace_ends_at' => null,
                    'suspended_at' => null,
                    'cancel_at_period_end' => false,
                    'cancelled_at' => null,
                    'cancellation_reason' => null,
                    'pending_plan_id' => null,
                    'pending_billing_cycle' => null,
                    'pending_plan_effective_at' => null,
                    'past_due_notified_at' => null,
                    'expiry_reminder_sent_at' => null,
                    'amount' => (float) $payment->amount,
                    'currency' => $payment->currency ?: $subscription->currency,
                ])->save();

                $event = 'renewed';
            } else {
                $event = $this->planChangeEvent($previousPlan, $targetPlan);

                $subscription->forceFill([
                    'plan_id' => $targetPlan->id,
                    'plan' => $targetPlan->code,
                    'billing_cycle' => $cycle,
                    'status' => SubscriptionStatus::Active,
                    'ends_at' => $cycle->addTo(now()),
                    'last_payment_at' => now(),
                    'grace_ends_at' => null,
                    'suspended_at' => null,
                    'cancel_at_period_end' => false,
                    'cancelled_at' => null,
                    'cancellation_reason' => null,
                    'pending_plan_id' => null,
                    'pending_billing_cycle' => null,
                    'pending_plan_effective_at' => null,
                    'past_due_notified_at' => null,
                    'expiry_reminder_sent_at' => null,
                    'amount' => (float) $payment->amount,
                    'currency' => $payment->currency ?: $subscription->currency,
                ])->save();
            }

            $this->recordHistory(
                $subscription,
                $event,
                $previousStatus,
                SubscriptionStatus::Active,
                $previousPlan,
                $targetPlan,
                $cycle,
                (float) $payment->amount,
                $admin,
                $note,
                ['payment_id' => $payment->id, 'payment_reference' => $payment->reference],
            );

            $payment->forceFill(['subscription_id' => $subscription->id])->save();

            return [$subscription, $payment];
        });

        $this->notifier->paymentConfirmed($payment->refresh());
        $this->notifier->subscriptionActivated($subscription);

        return $subscription;
    }

    /**
     * Refuse un paiement avec une justification obligatoire.
     */
    public function rejectPayment(Payment $payment, User $admin, string $reason): void
    {
        DB::transaction(function () use ($payment, $admin, $reason) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if (! $payment->isPending()) {
                throw ValidationException::withMessages([
                    'payment' => 'Ce paiement a déjà été traité et ne peut plus être refusé.',
                ]);
            }

            $payment->forceFill([
                'status' => PaymentStatus::Rejected,
                'rejection_reason' => $reason,
                'verified_at' => now(),
                'verified_by' => $admin->id,
            ])->save();

            if ($payment->subscription_id !== null) {
                $subscription = Subscription::query()->lockForUpdate()->find($payment->subscription_id);

                if ($subscription !== null) {
                    $previousStatus = $subscription->status;
                    $previousPlan = $subscription->planModel;

                    if ($subscription->status === SubscriptionStatus::Pending) {
                        $subscription->forceFill([
                            'status' => SubscriptionStatus::Cancelled,
                            'cancelled_at' => now(),
                            'cancellation_reason' => 'Paiement refusé : '.$reason,
                            'pending_plan_id' => null,
                            'pending_billing_cycle' => null,
                            'pending_plan_effective_at' => null,
                        ])->save();
                    } else {
                        $subscription->forceFill([
                            'pending_plan_id' => null,
                            'pending_billing_cycle' => null,
                            'pending_plan_effective_at' => null,
                        ])->save();
                    }

                    $this->recordHistory($subscription, 'payment_rejected', $previousStatus, $subscription->status, $previousPlan, $subscription->planModel, $subscription->billing_cycle, (float) $payment->amount, $admin, $reason, ['payment_id' => $payment->id]);
                }
            }
        });

        $this->notifier->paymentRejected($payment->refresh());
    }

    // ─── Administration du cycle de vie ──────────────────────

    /**
     * Résilie un abonnement.
     *
     * Un abonnement payé reste actif jusqu'à la fin de la période en cours.
     */
    public function cancel(Subscription $subscription, User $actor, ?string $reason = null): void
    {
        DB::transaction(function () use ($subscription, $actor, $reason) {
            $subscription = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);

            if ($subscription->status->isTerminal()) {
                return;
            }

            $previousStatus = $subscription->status;
            $previousPlan = $subscription->planModel;

            if ($subscription->grantsAccess() && $subscription->ends_at !== null && $subscription->ends_at->isFuture()) {
                $subscription->forceFill([
                    'cancel_at_period_end' => true,
                    'cancelled_at' => now(),
                    'cancellation_reason' => $reason,
                ])->save();

                $this->recordHistory($subscription, 'cancelled', $previousStatus, $subscription->status, $previousPlan, $previousPlan, $subscription->billing_cycle, (float) $subscription->amount, $actor, $reason ?? 'Résiliation à la fin de la période.');
            } else {
                $subscription->forceFill([
                    'status' => SubscriptionStatus::Cancelled,
                    'cancelled_at' => now(),
                    'ends_at' => now(),
                    'cancellation_reason' => $reason,
                    'pending_plan_id' => null,
                    'pending_billing_cycle' => null,
                    'pending_plan_effective_at' => null,
                ])->save();

                $this->recordHistory($subscription, 'cancelled', $previousStatus, SubscriptionStatus::Cancelled, $previousPlan, $previousPlan, $subscription->billing_cycle, (float) $subscription->amount, $actor, $reason ?? 'Résiliation immédiate.');
            }
        });
    }

    /**
     * Suspend un abonnement sans supprimer les données du professionnel.
     */
    public function suspend(Subscription $subscription, User $actor, ?string $reason = null): void
    {
        DB::transaction(function () use ($subscription, $actor, $reason) {
            $subscription = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);

            if ($subscription->status === SubscriptionStatus::Suspended) {
                return;
            }

            $previousStatus = $subscription->status;

            $subscription->forceFill([
                'status' => SubscriptionStatus::Suspended,
                'suspended_at' => now(),
            ])->save();

            $this->recordHistory($subscription, 'suspended', $previousStatus, SubscriptionStatus::Suspended, $subscription->planModel, $subscription->planModel, $subscription->billing_cycle, (float) $subscription->amount, $actor, $reason);
        });
    }

    /**
     * Réactive un abonnement suspendu ou expiré.
     */
    public function reactivate(Subscription $subscription, User $actor): void
    {
        $reactivated = DB::transaction(function () use ($subscription, $actor) {
            $subscription = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);

            if ($subscription->status === SubscriptionStatus::Active) {
                return null;
            }

            $previousStatus = $subscription->status;
            $cycle = $subscription->billing_cycle ?? BillingCycle::Monthly;

            $attributes = [
                'status' => SubscriptionStatus::Active,
                'suspended_at' => null,
                'grace_ends_at' => null,
                'past_due_notified_at' => null,
                'expiry_reminder_sent_at' => null,
            ];

            if ($subscription->ends_at === null || $subscription->ends_at->isPast()) {
                $attributes['starts_at'] = $subscription->starts_at ?? now();
                $attributes['ends_at'] = $cycle->addTo(now());
                $attributes['activated_at'] = $subscription->activated_at ?? now();
            }

            $subscription->forceFill($attributes)->save();

            $this->recordHistory($subscription, 'reactivated', $previousStatus, SubscriptionStatus::Active, $subscription->planModel, $subscription->planModel, $cycle, (float) $subscription->amount, $actor, 'Abonnement réactivé par l’administration.');

            return $subscription;
        });

        if ($reactivated !== null) {
            $this->notifier->subscriptionActivated($reactivated);
        }
    }

    /**
     * Prolonge l'échéance d'un abonnement.
     */
    public function extend(Subscription $subscription, CarbonInterface $endsAt, User $actor, ?string $note = null): void
    {
        $extended = DB::transaction(function () use ($subscription, $endsAt, $actor, $note) {
            $subscription = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);

            $previousStatus = $subscription->status;

            $subscription->forceFill([
                'status' => SubscriptionStatus::Active,
                'ends_at' => $endsAt,
                'suspended_at' => null,
                'grace_ends_at' => null,
                'past_due_notified_at' => null,
                'expiry_reminder_sent_at' => null,
            ])->save();

            $this->recordHistory($subscription, 'extended', $previousStatus, SubscriptionStatus::Active, $subscription->planModel, $subscription->planModel, $subscription->billing_cycle, (float) $subscription->amount, $actor, $note ?? 'Échéance prolongée par l’administration.', ['new_ends_at' => $endsAt->toIso8601String()]);

            return $subscription;
        });

        $this->notifier->subscriptionActivated($extended);
    }

    // ─── Tâche planifiée ─────────────────────────────────────

    /**
     * Traite les échéances, la grâce, les expirations et les rappels.
     *
     * @return array<string, int>
     */
    public function processLifecycle(): array
    {
        $counts = [
            'scheduled' => 0,
            'past_due' => 0,
            'expired' => 0,
            'reminders' => 0,
        ];

        $graceDays = max(0, (int) config('maliconnect.subscriptions.grace_days', 7));
        $reminderDays = max(1, (int) config('maliconnect.subscriptions.reminder_days', 7));

        Subscription::query()
            ->where(function ($query) {
                $query->whereIn('status', [
                    SubscriptionStatus::Active,
                    SubscriptionStatus::Trialing,
                    SubscriptionStatus::PastDue,
                ])->orWhereNotNull('pending_plan_effective_at');
            })
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use (&$counts, $graceDays, $reminderDays) {
                foreach ($subscriptions as $subscription) {
                    // Changement de formule arrivé à échéance.
                    if ($subscription->pending_plan_id !== null
                        && $subscription->pending_plan_effective_at !== null
                        && $subscription->pending_plan_effective_at->isPast()) {
                        $this->applyScheduledChange($subscription);
                        $subscription->refresh();
                        $counts['scheduled']++;
                    }

                    if ($subscription->status === SubscriptionStatus::Trialing
                        && $subscription->trial_ends_at !== null
                        && $subscription->trial_ends_at->isPast()) {
                        $subscription->forceFill(['status' => SubscriptionStatus::Expired])->save();
                        $this->recordHistory($subscription, 'expired', SubscriptionStatus::Trialing, SubscriptionStatus::Expired, $subscription->planModel, $subscription->planModel, $subscription->billing_cycle, (float) $subscription->amount, null, 'Fin de la période d’essai.');
                        $this->notifier->subscriptionExpired($subscription);
                        $counts['expired']++;

                        continue;
                    }

                    if (in_array($subscription->status, [SubscriptionStatus::Active, SubscriptionStatus::PastDue], true)
                        && $subscription->ends_at !== null
                        && $subscription->ends_at->isPast()) {
                        if ($subscription->grace_ends_at === null && $graceDays > 0) {
                            $subscription->forceFill([
                                'status' => SubscriptionStatus::PastDue,
                                'grace_ends_at' => $subscription->ends_at->copy()->addDays($graceDays),
                                'past_due_notified_at' => now(),
                            ])->save();

                            $this->recordHistory($subscription, 'past_due', SubscriptionStatus::Active, SubscriptionStatus::PastDue, $subscription->planModel, $subscription->planModel, $subscription->billing_cycle, (float) $subscription->amount, null, 'Période de grâce ouverte après échéance.');
                            $this->notifier->renewalNeeded($subscription);
                            $counts['past_due']++;
                        } elseif ($subscription->grace_ends_at !== null && $subscription->grace_ends_at->isPast()) {
                            $subscription->forceFill(['status' => SubscriptionStatus::Expired])->save();
                            $this->recordHistory($subscription, 'expired', SubscriptionStatus::PastDue, SubscriptionStatus::Expired, $subscription->planModel, $subscription->planModel, $subscription->billing_cycle, (float) $subscription->amount, null, 'Expiration après la période de grâce.');
                            $this->notifier->subscriptionExpired($subscription);
                            $counts['expired']++;
                        } elseif ($graceDays === 0) {
                            // Sans période de grâce, l'échéance dépassée coupe
                            // immédiatement l'accès aux fonctionnalités.
                            $subscription->forceFill(['status' => SubscriptionStatus::Expired])->save();
                            $this->recordHistory($subscription, 'expired', SubscriptionStatus::Active, SubscriptionStatus::Expired, $subscription->planModel, $subscription->planModel, $subscription->billing_cycle, (float) $subscription->amount, null, 'Expiration immédiate à l’échéance.');
                            $this->notifier->subscriptionExpired($subscription);
                            $counts['expired']++;
                        }

                        continue;
                    }

                    if ($subscription->status === SubscriptionStatus::Active
                        && $subscription->ends_at !== null
                        && $subscription->ends_at->isFuture()
                        && $subscription->ends_at->lessThanOrEqualTo(now()->addDays($reminderDays))
                        && $subscription->expiry_reminder_sent_at === null) {
                        $subscription->forceFill(['expiry_reminder_sent_at' => now()])->save();
                        $this->notifier->subscriptionExpiringSoon($subscription);
                        $counts['reminders']++;
                    }
                }
            });

        return $counts;
    }

    /**
     * Applique un changement de formule programmé arrivé à échéance.
     */
    public function applyScheduledChange(Subscription $subscription, ?User $actor = null): void
    {
        DB::transaction(function () use ($subscription, $actor) {
            $subscription = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);

            if ($subscription->pending_plan_id === null
                || $subscription->pending_plan_effective_at === null
                || $subscription->pending_plan_effective_at->isFuture()) {
                return;
            }

            $targetPlan = $subscription->pendingPlan;

            if ($targetPlan === null) {
                $subscription->forceFill([
                    'pending_plan_id' => null,
                    'pending_billing_cycle' => null,
                    'pending_plan_effective_at' => null,
                ])->save();

                return;
            }

            $previousStatus = $subscription->status;
            $previousPlan = $subscription->planModel;

            if ($targetPlan->isFree()) {
                $subscription->forceFill([
                    'plan_id' => $targetPlan->id,
                    'plan' => $targetPlan->code,
                    'billing_cycle' => BillingCycle::Monthly,
                    'status' => SubscriptionStatus::Cancelled,
                    'cancelled_at' => now(),
                    'cancellation_reason' => $subscription->cancellation_reason ?? 'Passage à la formule FREE à la fin de la période payée.',
                    'pending_plan_id' => null,
                    'pending_billing_cycle' => null,
                    'pending_plan_effective_at' => null,
                    'amount' => 0,
                ])->save();

                $this->recordHistory($subscription, 'downgraded', $previousStatus, SubscriptionStatus::Cancelled, $previousPlan, $targetPlan, BillingCycle::Monthly, 0, $actor, 'Passage à la formule FREE à la fin de la période payée.');
            } else {
                $cycle = $subscription->pending_billing_cycle ?? BillingCycle::Monthly;

                $subscription->forceFill([
                    'plan_id' => $targetPlan->id,
                    'plan' => $targetPlan->code,
                    'billing_cycle' => $cycle,
                    'status' => SubscriptionStatus::Active,
                    'ends_at' => $cycle->addTo(now()),
                    'pending_plan_id' => null,
                    'pending_billing_cycle' => null,
                    'pending_plan_effective_at' => null,
                    'amount' => $targetPlan->priceFor($cycle),
                ])->save();

                $this->recordHistory($subscription, 'plan_changed', $previousStatus, SubscriptionStatus::Active, $previousPlan, $targetPlan, $cycle, $targetPlan->priceFor($cycle), $actor, 'Changement de formule programmé appliqué.');
            }
        });
    }

    // ─── Helpers ─────────────────────────────────────────────

    /**
     * Retrouve ou crée l'abonnement visé par un paiement.
     */
    private function resolveSubscriptionForPayment(Payment $payment): ?Subscription
    {
        if ($payment->subscription_id !== null) {
            return Subscription::query()->find($payment->subscription_id);
        }

        if ($payment->business_id !== null) {
            $subscription = Subscription::query()
                ->where('business_id', $payment->business_id)
                ->lockForUpdate()
                ->first();

            if ($subscription === null) {
                $subscription = Subscription::create([
                    'business_id' => $payment->business_id,
                    'plan_id' => $payment->plan_id,
                    'plan' => $payment->plan?->code ?? 'free',
                    'billing_cycle' => $payment->billing_cycle ?? BillingCycle::Monthly,
                    'status' => SubscriptionStatus::Pending,
                    'amount' => (float) $payment->amount,
                    'currency' => $payment->currency,
                ]);
            }

            $payment->forceFill(['subscription_id' => $subscription->id])->save();

            return $subscription;
        }

        if ($payment->provider_profile_id !== null) {
            $subscription = Subscription::query()
                ->where('provider_profile_id', $payment->provider_profile_id)
                ->whereNull('business_id')
                ->lockForUpdate()
                ->first();

            if ($subscription === null) {
                $subscription = Subscription::create([
                    'provider_profile_id' => $payment->provider_profile_id,
                    'plan_id' => $payment->plan_id,
                    'plan' => $payment->plan?->code ?? 'free',
                    'billing_cycle' => $payment->billing_cycle ?? BillingCycle::Monthly,
                    'status' => SubscriptionStatus::Pending,
                    'amount' => (float) $payment->amount,
                    'currency' => $payment->currency,
                ]);
            }

            $payment->forceFill(['subscription_id' => $subscription->id])->save();

            return $subscription;
        }

        return null;
    }

    /**
     * Détermine le libellé d'un changement de formule.
     */
    private function planChangeEvent(?Plan $from, Plan $to): string
    {
        if ($from === null) {
            return 'plan_changed';
        }

        if ($to->sort_order > $from->sort_order) {
            return 'upgraded';
        }

        if ($to->sort_order < $from->sort_order) {
            return 'downgraded';
        }

        return 'plan_changed';
    }

    /**
     * Enregistre un événement d'historique.
     */
    private function recordHistory(
        Subscription $subscription,
        string $event,
        ?SubscriptionStatus $fromStatus,
        ?SubscriptionStatus $toStatus,
        ?Plan $fromPlan,
        ?Plan $toPlan,
        ?BillingCycle $cycle,
        ?float $amount,
        ?User $actor = null,
        ?string $note = null,
        array $metadata = [],
    ): void {
        SubscriptionHistory::create([
            'subscription_id' => $subscription->id,
            'actor_id' => $actor?->id,
            'event' => $event,
            'from_status' => $fromStatus?->value,
            'to_status' => $toStatus?->value,
            'from_plan_id' => $fromPlan?->id,
            'to_plan_id' => $toPlan?->id,
            'billing_cycle' => $cycle?->value,
            'amount' => $amount,
            'currency' => $subscription->currency ?: 'XOF',
            'starts_at' => $subscription->starts_at,
            'ends_at' => $subscription->ends_at,
            'note' => $note,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    /**
     * Extrait les identifiants de propriétaire.
     *
     * @return array{0: ?ProviderProfile, 1: ?Business}
     */
    private function ownerIds(ProviderProfile|Business $owner): array
    {
        return $owner instanceof Business
            ? [null, $owner]
            : [$owner, null];
    }

    /**
     * Ajoute une note sans écraser l'existant.
     */
    private function appendNote(?string $existing, ?string $note): ?string
    {
        if ($note === null || trim($note) === '') {
            return $existing;
        }

        return trim(($existing !== null && $existing !== '' ? $existing."\n" : '').$note);
    }
}
