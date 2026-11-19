<?php

namespace App\Models;

use App\Enums\BusinessStatus;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Entreprise portant un ou plusieurs profils professionnels.
 *
 * Les collaborateurs et leurs rôles sont décrits par BusinessMember.
 */
class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'legal_name',
        'description',
        'phone',
        'email',
        'address',
        'city',
        'commune',
        'logo_path',
    ];

    protected function casts(): array
    {
        return [
            'status' => BusinessStatus::class,
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    /**
     * Compte à l'origine de la fiche.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Collaborateurs de l'entreprise.
     */
    public function members(): HasMany
    {
        return $this->hasMany(BusinessMember::class);
    }

    /**
     * Utilisateurs membres de l'entreprise.
     */
    public function memberUsers(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, BusinessMember::class);
    }

    /**
     * Profils professionnels portés par l'entreprise.
     */
    public function profiles(): HasMany
    {
        return $this->hasMany(ProviderProfile::class);
    }

    /**
     * Abonnements de l'entreprise.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Administrateur ayant validé l'entreprise.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ─── Helpers ──────────────────────────────────────────────

    /**
     * Abonnement courant qui ouvre encore droit aux fonctionnalités.
     */
    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()->grantingAccess()->latest('starts_at')->latest('id')->first();
    }

    /**
     * Indique si l'entreprise a été vérifiée.
     */
    public function isVerified(): bool
    {
        return $this->status === BusinessStatus::Verified;
    }

    /**
     * Retourne le rôle d'un utilisateur au sein de l'entreprise.
     */
    public function roleOf(User $user): ?BusinessMember
    {
        return $this->members->firstWhere('user_id', $user->id);
    }

    // ─── Scopes ───────────────────────────────────────────────

    /**
     * Limite aux entreprises vérifiées.
     */
    public function scopeVerified($query)
    {
        return $query->where('status', BusinessStatus::Verified);
    }
}
