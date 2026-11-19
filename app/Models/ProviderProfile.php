<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\BusinessStatus;
use App\Enums\MediaType;
use App\Enums\ProfileStatus;
use App\Enums\ReviewStatus;
use Database\Factories\ProviderProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * Profil professionnel d'un prestataire.
 *
 * Le profil est la propriété du compte qui l'a créé et peut être rattaché à
 * une entreprise. Sa présentation publique comme profil « vérifié » est
 * conditionnée par le statut de vérification administrative.
 */
class ProviderProfile extends Model
{
    /** @use HasFactory<ProviderProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',
        'category_id',
        'subcategory_id',
        'business_name',
        'slug',
        'description',
        'logo_path',
        'phone',
        'whatsapp',
        'email',
        'address',
        'city',
        'commune',
        'quartier',
        'latitude',
        'longitude',
        'years_experience',
        'response_time_hours',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProfileStatus::class,
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
            'rating_average' => 'decimal:2',
            'rating_count' => 'integer',
            'years_experience' => 'integer',
            'response_time_hours' => 'integer',
            'subscription_expires_at' => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    /**
     * Compte propriétaire du profil.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Entreprise portant le profil.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    /**
     * Zones d'intervention du profil.
     */
    public function serviceAreas(): HasMany
    {
        return $this->hasMany(ServiceArea::class);
    }

    /**
     * Médias (photos, réalisations, couverture).
     */
    public function media(): HasMany
    {
        return $this->hasMany(ProviderProfileMedia::class)->orderBy('position');
    }

    /**
     * Horaires d'ouverture hebdomadaires.
     */
    public function hours(): HasMany
    {
        return $this->hasMany(ProviderProfileHour::class)->orderBy('day_of_week');
    }

    /**
     * Disponibilités ponctuelles.
     */
    public function availabilities(): HasMany
    {
        return $this->hasMany(ProviderProfileAvailability::class)->orderBy('date');
    }

    /**
     * Services proposés par le profil.
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Demandes de devis reçues par le profil.
     */
    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    /**
     * Devis envoyés par le profil.
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(ServiceRequestQuote::class);
    }

    /**
     * Réservations du profil.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(ProviderBooking::class);
    }

    /**
     * Avis reçus par le profil.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProviderReview::class);
    }

    /**
     * Avis approuvés, seuls visibles publiquement.
     */
    public function approvedReviews(): HasMany
    {
        return $this->hasMany(ProviderReview::class)->where('status', ReviewStatus::Approved);
    }

    /**
     * Utilisateurs ayant mis le profil en favori.
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Fiches tarifaires du profil.
     */
    public function pricingSheets(): HasMany
    {
        return $this->hasMany(PricingSheet::class);
    }

    /**
     * Abonnements du profil.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Paiements rattachés au profil.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Administrateur ayant validé le profil.
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
        return $this->subscriptions()
            ->whereNull('business_id')
            ->grantingAccess()
            ->latest('starts_at')
            ->latest('id')
            ->first();
    }

    /**
     * Indique si le profil a été vérifié par l'administration.
     */
    public function isVerified(): bool
    {
        return $this->status === ProfileStatus::Verified;
    }

    /**
     * Indique si le profil peut être présenté publiquement comme vérifié.
     *
     * Un profil en attente, refusé ou suspendu ne l'est jamais. La suspension
     * du compte propriétaire ou de l'entreprise porteuse masque aussi le
     * profil, sans modifier son état de vérification.
     */
    public function isPubliclyVisible(): bool
    {
        if (! $this->isVerified() || ! $this->is_active) {
            return false;
        }

        if ($this->user?->status !== AccountStatus::Active) {
            return false;
        }

        return $this->business_id === null
            || $this->business?->status === BusinessStatus::Verified;
    }

    /**
     * Indique si le profil attend une vérification administrative.
     */
    public function isPending(): bool
    {
        return $this->status === ProfileStatus::Pending;
    }

    /**
     * Indique si le profil peut être soumis à vérification.
     */
    public function canBeSubmitted(): bool
    {
        return in_array($this->status, [ProfileStatus::Pending, ProfileStatus::Refused, ProfileStatus::Suspended], true);
    }

    /**
     * Indique si l'utilisateur pilote le profil.
     *
     * Le propriétaire du profil et les collaborateurs habilités de l'entreprise
     * qui le porte peuvent le piloter.
     */
    public function isManagedBy(User $user): bool
    {
        // Une entreprise suspendue ne peut plus piloter les profils qu'elle porte.
        if ($this->business_id !== null && $this->business?->status === BusinessStatus::Suspended) {
            return false;
        }

        if ($this->user_id === $user->id) {
            return true;
        }

        if ($this->business_id === null) {
            return false;
        }

        $membership = $user->businessMemberships()
            ->where('business_id', $this->business_id)
            ->first();

        return $membership?->canManageProfiles() ?? false;
    }

    /**
     * Comptes habilités à piloter le profil et à recevoir ses notifications.
     *
     * Il s'agit du propriétaire du profil, du responsable de l'entreprise qui
     * le porte et des collaborateurs disposant du droit de gérer les profils.
     *
     * @return Collection<int, User>
     */
    public function managers(): Collection
    {
        $managers = collect();

        if ($this->user !== null) {
            $managers->push($this->user);
        }

        if ($this->business_id === null) {
            return $managers->unique('id')->values();
        }

        $business = $this->business;

        if ($business?->owner !== null) {
            $managers->push($business->owner);
        }

        $business?->members()
            ->where('status', 'active')
            ->with('user')
            ->get()
            ->filter(fn (BusinessMember $member) => $member->canManageProfiles() && $member->user !== null)
            ->each(fn (BusinessMember $member) => $managers->push($member->user));

        return $managers->unique('id')->values();
    }

    /**
     * Couverture du profil (premier média de type couverture).
     */
    public function cover(): HasOne
    {
        return $this->hasOne(ProviderProfileMedia::class)->where('type', MediaType::Cover);
    }

    // ─── Scopes ───────────────────────────────────────────────

    /**
     * Limite aux profils visibles publiquement.
     *
     * Le profil doit être vérifié et actif, son compte propriétaire actif, et
     * l'entreprise porteuse vérifiée lorsqu'il y en a une.
     */
    public function scopePubliclyVisible($query)
    {
        return $query
            ->where('status', ProfileStatus::Verified)
            ->where('is_active', true)
            ->whereHas('user', fn ($query) => $query->where('status', AccountStatus::Active))
            ->where(function ($query) {
                $query->whereNull('business_id')
                    ->orWhereHas('business', fn ($query) => $query->where('status', BusinessStatus::Verified));
            });
    }

    /**
     * Limite aux profils d'un compte donné.
     */
    public function scopeOwnedBy($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Limite aux profils pilotés par l'utilisateur.
     *
     * Inclut les profils portés par une entreprise dont l'utilisateur est
     * collaborateur.
     */
    public function scopeManagedBy($query, User $user)
    {
        $businessIds = $user->businessMemberships()->pluck('business_id');

        return $query->where(function ($query) use ($user, $businessIds) {
            $query->where('user_id', $user->id);

            if ($businessIds->isNotEmpty()) {
                $query->orWhereIn('business_id', $businessIds);
            }
        });
    }

    /**
     * Limite aux profils en attente de vérification.
     */
    public function scopePendingReview($query)
    {
        return $query->where('status', ProfileStatus::Pending);
    }
}
