<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionStatus;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Abonnement courant d'un propriétaire professionnel.
 *
 * Le propriétaire est soit un profil indépendant (`provider_profile_id`),
 * soit une entreprise (`business_id`). Un seul abonnement courant existe par
 * propriétaire : les changements sont conservés dans `subscription_histories`.
 *
 * Le montant porté ici est contractuel. Les revenus affichés dans
 * l'administration proviennent uniquement des paiements réellement payés.
 */
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    protected $fillable = [
        'provider_profile_id',
        'business_id',
        'plan_id',
        'pending_plan_id',
        'pending_billing_cycle',
        'pending_plan_effective_at',
        'plan',
        'billing_cycle',
        'status',
        'starts_at',
        'ends_at',
        'activated_at',
        'trial_ends_at',
        'grace_ends_at',
        'suspended_at',
        'cancelled_at',
        'cancel_at_period_end',
        'cancellation_reason',
        'renewed_at',
        'last_payment_at',
        'expiry_reminder_sent_at',
        'past_due_notified_at',
        'amount',
        'currency',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'billing_cycle' => BillingCycle::class,
            'pending_billing_cycle' => BillingCycle::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'activated_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'grace_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'cancel_at_period_end' => 'boolean',
            'renewed_at' => 'datetime',
            'last_payment_at' => 'datetime',
            'expiry_reminder_sent_at' => 'datetime',
            'past_due_notified_at' => 'datetime',
            'pending_plan_effective_at' => 'datetime',
            'amount' => 'decimal:0',
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    /**
     * Profil professionnel abonné (abonnement indépendant).
     */
    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    /**
     * Entreprise abonnée (abonnement multi-profils).
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Plan commercial souscrit.
     */
    public function planModel(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    /**
     * Plan programmé à la fin de la période en cours.
     */
    public function pendingPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'pending_plan_id');
    }

    /**
     * Paiements rattachés à l'abonnement.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Historique complet des événements.
     */
    public function histories(): HasMany
    {
        return $this->hasMany(SubscriptionHistory::class)->latest('created_at')->latest('id');
    }

    // ─── Scopes ───────────────────────────────────────────────

    /**
     * Limite aux abonnements réellement actifs à l'instant présent.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            SubscriptionStatus::Active,
            SubscriptionStatus::Trialing,
        ])->where(function (Builder $query) {
            $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
        });
    }

    /**
     * Limite aux abonnements qui ouvrent encore droit aux fonctionnalités.
     *
     * La période de grâce est prise en compte pour les abonnements en retard.
     */
    public function scopeGrantingAccess(Builder $query): Builder
    {
        return $query->whereIn('status', [
            SubscriptionStatus::Active,
            SubscriptionStatus::Trialing,
            SubscriptionStatus::PastDue,
        ])->where(function (Builder $query) {
            $query->whereNull('ends_at')
                ->orWhere('ends_at', '>', now())
                ->orWhere('grace_ends_at', '>', now());
        });
    }

    /**
     * Limite aux abonnements d'un propriétaire donné.
     */
    public function scopeForOwner(Builder $query, ?ProviderProfile $profile = null, ?Business $business = null): Builder
    {
        if ($business !== null) {
            return $query->where('business_id', $business->id);
        }

        if ($profile !== null) {
            return $query->where('provider_profile_id', $profile->id)->whereNull('business_id');
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Limite aux abonnements visibles par un utilisateur.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        $businessIds = $user->businessMemberships()->pluck('business_id');

        return $query->where(function (Builder $query) use ($user, $businessIds) {
            $query->whereHas('providerProfile', function (Builder $profile) use ($user) {
                $profile->where('user_id', $user->id);
            });

            if ($businessIds->isNotEmpty()) {
                $query->orWhereIn('business_id', $businessIds);
            }
        });
    }

    // ─── Propriétaire ─────────────────────────────────────────

    /**
     * Indique si l'abonnement appartient à une entreprise.
     */
    public function belongsToBusiness(): bool
    {
        return $this->business_id !== null;
    }

    /**
     * Nom du propriétaire affiché dans l'interface.
     */
    public function ownerLabel(): string
    {
        return $this->business?->name
            ?? $this->providerProfile?->business_name
            ?? 'Propriétaire inconnu';
    }

    /**
     * Type de propriétaire.
     */
    public function ownerType(): string
    {
        return $this->belongsToBusiness() ? 'business' : 'profile';
    }

    /**
     * Comptes autorisés à consulter l'abonnement.
     *
     * @return Collection<int, User>
     */
    public function ownerUsers(): Collection
    {
        if ($this->business !== null) {
            $business = $this->business;

            $users = collect();

            if ($business->owner !== null) {
                $users->push($business->owner);
            }

            $business->members()
                ->where('status', 'active')
                ->with('user')
                ->get()
                ->filter(fn (BusinessMember $member) => $member->canManageProfiles() && $member->user !== null)
                ->each(fn (BusinessMember $member) => $users->push($member->user));

            return $users->unique('id')->values();
        }

        return $this->providerProfile?->managers() ?? collect();
    }

    /**
     * Indique si un utilisateur peut consulter l'abonnement.
     */
    public function isOwnedBy(User $user): bool
    {
        return $this->ownerUsers()->contains('id', $user->id);
    }

    // ─── Cycle de vie ─────────────────────────────────────────

    /**
     * Indique si l'abonnement ouvre encore droit aux fonctionnalités.
     */
    public function grantsAccess(): bool
    {
        if (! $this->status->grantsAccess()) {
            return false;
        }

        if ($this->ends_at === null) {
            return true;
        }

        if ($this->ends_at->isFuture()) {
            return true;
        }

        return $this->grace_ends_at !== null && $this->grace_ends_at->isFuture();
    }

    /**
     * Indique si l'abonnement est actif et non expiré.
     */
    public function isActive(): bool
    {
        return $this->status->isActive()
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    /**
     * Indique si l'abonnement est suspendu.
     */
    public function isSuspended(): bool
    {
        return $this->status === SubscriptionStatus::Suspended;
    }

    /**
     * Indique si l'échéance est dépassée et la grâce terminée.
     */
    public function isExpired(): bool
    {
        return $this->status->isTerminal()
            || ($this->ends_at !== null && $this->ends_at->isPast() && ($this->grace_ends_at === null || $this->grace_ends_at->isPast()));
    }

    /**
     * Nombre de jours restants avant l'échéance.
     */
    public function daysRemaining(): ?int
    {
        if ($this->ends_at === null) {
            return null;
        }

        return max(0, (int) now()->startOfDay()->diffInDays($this->ends_at->copy()->startOfDay(), false));
    }

    /**
     * Plan effectif, en tenant compte d'un changement déjà arrivé à échéance.
     */
    public function effectivePlan(): ?Plan
    {
        if ($this->pending_plan_id !== null
            && $this->pending_plan_effective_at !== null
            && $this->pending_plan_effective_at->isPast()) {
            return $this->pendingPlan;
        }

        return $this->planModel;
    }

    /**
     * Libellé de la périodicité.
     */
    public function billingCycleLabel(): string
    {
        return $this->billing_cycle?->label() ?? 'Mensuel';
    }

    /**
     * Montant formaté.
     */
    public function formattedAmount(): string
    {
        return number_format((float) $this->amount, 0, ',', ' ').' '.$this->currency;
    }

    /**
     * Indique si un changement de formule est programmé.
     */
    public function hasScheduledChange(): bool
    {
        return $this->pending_plan_id !== null;
    }
}
