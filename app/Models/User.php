<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Compte utilisateur de MaliConnect.
 *
 * L'utilisateur porte son identité (nom, e-mail, téléphone), son statut
 * d'activité (AccountStatus) et ses rôles (Spatie Laravel Permission).
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, MustVerifyEmailTrait, Notifiable;

    /**
     * Valeurs par défaut appliquées aux nouvelles instances.
     *
     * Le statut est toujours présent en mémoire afin que les contrôles
     * d'accès ne dépendent pas d'un rechargement depuis la base.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * Attributs assignables en masse.
     *
     * Le statut n'y figure pas : il est réservé aux administrateurs et
     * n'est jamais accepté depuis une requête utilisateur.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'locale',
    ];

    /**
     * Attributs masqués lors de la sérialisation.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Récupère les attributs à caster.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'status' => AccountStatus::class,
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    public function professional(): HasOne
    {
        return $this->hasOne(Professional::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'customer_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'customer_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'customer_id');
    }

    /**
     * Profils professionnels créés par le compte.
     */
    public function providerProfiles(): HasMany
    {
        return $this->hasMany(ProviderProfile::class);
    }

    /**
     * Entreprises créées par le compte.
     */
    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class, 'owner_id');
    }

    /**
     * Collaborations du compte au sein d'entreprises.
     */
    public function businessMemberships(): HasMany
    {
        return $this->hasMany(BusinessMember::class);
    }

    /**
     * Demandes de devis adressées par le compte.
     */
    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'customer_id');
    }

    /**
     * Réservations prises par le compte.
     */
    public function providerBookings(): HasMany
    {
        return $this->hasMany(ProviderBooking::class, 'customer_id');
    }

    /**
     * Avis déposés par le compte.
     */
    public function providerReviews(): HasMany
    {
        return $this->hasMany(ProviderReview::class, 'customer_id');
    }

    /**
     * Professionnels mis en favori par le compte.
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Profils professionnels favoris du compte.
     */
    public function favoriteProfiles(): BelongsToMany
    {
        return $this->belongsToMany(ProviderProfile::class, 'favorites')->withTimestamps();
    }

    /**
     * Paiements enregistrés par le compte.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Signalements déposés par le compte.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    // ─── Helpers ──────────────────────────────────────────────

    /**
     * Indique si le compte est actif et peut accéder à l'application.
     */
    public function isActive(): bool
    {
        return $this->status === AccountStatus::Active;
    }

    /**
     * Indique si le compte est suspendu par un administrateur.
     */
    public function isSuspended(): bool
    {
        return $this->status === AccountStatus::Suspended;
    }

    /**
     * Indique si l'utilisateur dispose d'un accès à l'administration.
     */
    public function hasAdminAccess(): bool
    {
        return $this->hasRole([UserRole::SuperAdmin->value, UserRole::Admin->value]);
    }

    /**
     * Indique si l'utilisateur est super administrateur.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(UserRole::SuperAdmin->value);
    }

    public function isProfessional(): bool
    {
        return $this->hasRole([UserRole::Professional->value, UserRole::CompanyManager->value]);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin->value);
    }

    public function isCustomer(): bool
    {
        return $this->hasRole(UserRole::Customer->value);
    }
}
