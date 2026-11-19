<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Devis envoyé par un professionnel en réponse à une demande.
 */
class ServiceRequestQuote extends Model
{
    protected $table = 'service_request_quotes';

    protected $fillable = [
        'service_request_id',
        'provider_profile_id',
        'service_id',
        'message',
        'amount',
        'duration_minutes',
        'valid_until',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'amount' => 'decimal:0',
            'duration_minutes' => 'integer',
            'valid_until' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Réservation issue de l'acceptation de ce devis.
     */
    public function booking(): HasOne
    {
        return $this->hasOne(ProviderBooking::class, 'service_request_quote_id');
    }

    // ─── Boot ─────────────────────────────────────────────────

    /**
     * Génère la référence du devis.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $quote) {
            $quote->reference = 'DV-'.strtoupper(uniqid());
        });
    }
}
