<?php

namespace App\Models;

use App\Enums\ServiceRequestStatus;
use Database\Factories\ServiceRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Demande de devis adressée par un client à un professionnel.
 */
class ServiceRequest extends Model
{
    /** @use HasFactory<ServiceRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'provider_profile_id',
        'service_id',
        'pricing_item_id',
        'category_id',
        'title',
        'description',
        'address',
        'city',
        'commune',
        'quartier',
        'latitude',
        'longitude',
        'budget_min',
        'budget_max',
        'desired_date',
        'preferred_time',
    ];

    protected function casts(): array
    {
        return [
            'status' => ServiceRequestStatus::class,
            'budget_min' => 'decimal:0',
            'budget_max' => 'decimal:0',
            'desired_date' => 'date',
            'responded_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    /**
     * Client à l'origine de la demande.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Profil professionnel destinataire.
     */
    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Prestation tarifée à l'origine de la demande, si applicable.
     */
    public function pricingItem(): BelongsTo
    {
        return $this->belongsTo(PricingItem::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Devis envoyés en réponse.
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(ServiceRequestQuote::class);
    }

    /**
     * Échanges (questions et réponses).
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ServiceRequestMessage::class)->oldest();
    }

    /**
     * Pièces jointes.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ServiceRequestAttachment::class);
    }

    /**
     * Réservations issues de la demande.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(ProviderBooking::class);
    }

    // ─── Helpers ──────────────────────────────────────────────

    /**
     * Indique si l'utilisateur est le client de la demande.
     */
    public function isOwnedByCustomer(User $user): bool
    {
        return $this->customer_id === $user->id;
    }

    /**
     * Indique si l'utilisateur possède le profil professionnel destinataire.
     */
    public function isOwnedByProfessional(User $user): bool
    {
        return $this->providerProfile !== null && $this->providerProfile->isManagedBy($user);
    }

    // ─── Boot ─────────────────────────────────────────────────

    /**
     * Génère la référence de la demande.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $request) {
            $request->reference = 'DM-'.strtoupper(uniqid());
        });
    }
}
