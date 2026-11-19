<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Paiement enregistré sur la plateforme.
 *
 * Un paiement en attente n'est jamais comptabilisé comme revenu. Seuls les
 * paiements au statut « paid » alimentent les indicateurs financiers.
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'reference',
        'external_reference',
        'subscription_id',
        'plan_id',
        'billing_cycle',
        'user_id',
        'provider_profile_id',
        'business_id',
        'amount',
        'currency',
        'method',
        'gateway',
        'status',
        'paid_at',
        'declared_at',
        'verified_at',
        'verified_by',
        'notes',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'method' => PaymentMethod::class,
            'billing_cycle' => BillingCycle::class,
            'amount' => 'decimal:0',
            'paid_at' => 'datetime',
            'declared_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    /**
     * Abonnement concerné par le paiement.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Formule visée par le paiement.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Compte à l'origine du paiement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Profil professionnel concerné.
     */
    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    /**
     * Entreprise concernée.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Preuves de paiement déposées.
     */
    public function proofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class);
    }

    /**
     * Administrateur ayant vérifié le paiement.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ─── Scopes ───────────────────────────────────────────────

    /**
     * Limite aux paiements réellement encaissés.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Paid);
    }

    /**
     * Limite aux paiements en attente de vérification.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Pending);
    }

    /**
     * Limite aux paiements visibles par un utilisateur.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        $businessIds = $user->businessMemberships()->pluck('business_id');

        return $query->where(function (Builder $query) use ($user, $businessIds) {
            $query->where('user_id', $user->id)
                ->orWhereHas('providerProfile', fn (Builder $profile) => $profile->where('user_id', $user->id));

            if ($businessIds->isNotEmpty()) {
                $query->orWhereIn('business_id', $businessIds);
            }
        });
    }

    // ─── Helpers ──────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }

    public function isRejected(): bool
    {
        return $this->status === PaymentStatus::Rejected;
    }

    /**
     * Nom du propriétaire concerné par le paiement.
     */
    public function ownerLabel(): string
    {
        return $this->business?->name
            ?? $this->providerProfile?->business_name
            ?? $this->user?->name
            ?? 'Compte inconnu';
    }

    /**
     * Montant formaté.
     */
    public function formattedAmount(): string
    {
        return number_format((float) $this->amount, 0, ',', ' ').' '.$this->currency;
    }

    /**
     * Comptes autorisés à consulter le paiement.
     *
     * @return Collection<int, User>
     */
    public function ownerUsers(): Collection
    {
        $users = collect();

        if ($this->user !== null) {
            $users->push($this->user);
        }

        if ($this->business !== null) {
            $business = $this->business;

            if ($business->owner !== null) {
                $users->push($business->owner);
            }

            $business->members()
                ->where('status', 'active')
                ->with('user')
                ->get()
                ->filter(fn (BusinessMember $member) => $member->canManageProfiles() && $member->user !== null)
                ->each(fn (BusinessMember $member) => $users->push($member->user));
        }

        if ($this->providerProfile !== null) {
            $this->providerProfile->managers()->each(fn (User $manager) => $users->push($manager));
        }

        return $users->unique('id')->values();
    }

    /**
     * Indique si un utilisateur peut consulter le paiement.
     */
    public function isOwnedBy(User $user): bool
    {
        return $this->ownerUsers()->contains('id', $user->id);
    }

    // ─── Boot ─────────────────────────────────────────────────

    /**
     * Génère la référence unique du paiement.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $payment) {
            $payment->reference = $payment->reference ?: 'PAY-'.strtoupper(uniqid());
            $payment->declared_at ??= now();
        });
    }
}
