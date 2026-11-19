<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Database\Factories\ProviderBookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Réservation d'une prestation chez un professionnel.
 */
class ProviderBooking extends Model
{
    /** @use HasFactory<ProviderBookingFactory> */
    use HasFactory;

    protected $table = 'provider_bookings';

    protected $fillable = [
        'customer_id',
        'provider_profile_id',
        'service_request_id',
        'service_request_quote_id',
        'service_id',
        'starts_at',
        'ends_at',
        'duration_minutes',
        'amount',
        'address',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'amount' => 'decimal:0',
            'duration_minutes' => 'integer',
            'cancelled_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(ServiceRequestQuote::class, 'service_request_quote_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Avis déposé sur la prestation.
     */
    public function review(): HasOne
    {
        return $this->hasOne(ProviderReview::class, 'provider_booking_id');
    }

    // ─── Helpers ──────────────────────────────────────────────

    /**
     * Indique si l'utilisateur est partie prenante de la réservation.
     */
    public function involves(User $user): bool
    {
        return $this->customer_id === $user->id
            || ($this->providerProfile !== null && $this->providerProfile->isManagedBy($user));
    }

    /**
     * Indique si le client peut déposer un avis sur cette prestation.
     *
     * L'avis n'est possible qu'après une prestation réellement terminée :
     * le statut doit être « completed », la clôture et le créneau passé
     * doivent être renseignés, et aucun avis ne doit déjà exister.
     */
    public function isReviewableBy(User $user): bool
    {
        if ($this->customer_id !== $user->id) {
            return false;
        }

        if ($this->status !== BookingStatus::Completed || $this->completed_at === null) {
            return false;
        }

        if ($this->starts_at === null || $this->starts_at->isFuture()) {
            return false;
        }

        return ! $this->review()->exists();
    }

    // ─── Boot ─────────────────────────────────────────────────

    /**
     * Génère la référence de la réservation.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $booking) {
            $booking->reference = 'RV-'.strtoupper(uniqid());
        });
    }
}
