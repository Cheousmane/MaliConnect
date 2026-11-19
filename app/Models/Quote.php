<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'professional_id',
        'service_id',
        'customer_id',
        'reference',
        'message',
        'budget_min',
        'budget_max',
        'desired_date',
        'address',
        'status',
        'professional_message',
        'quoted_amount',
        'quoted_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'budget_min' => 'decimal:0',
            'budget_max' => 'decimal:0',
            'quoted_amount' => 'decimal:0',
            'desired_date' => 'date',
            'quoted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    // ─── Statuts ──────────────────────────────────────────────

    const STATUS_PENDING = 'pending';    // En attente de réponse pro

    const STATUS_QUOTED = 'quoted';     // Pro a répondu avec un devis

    const STATUS_ACCEPTED = 'accepted';   // Client a accepté

    const STATUS_REJECTED = 'rejected';   // Client a refusé

    const STATUS_CANCELLED = 'cancelled';  // Annulé

    const STATUS_EXPIRED = 'expired';    // Expiré sans réponse

    const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_QUOTED,
        self::STATUS_ACCEPTED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
        self::STATUS_EXPIRED,
    ];

    // ─── Relations ────────────────────────────────────────────

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeForProfessional($query, int $professionalId)
    {
        return $query->where('professional_id', $professionalId);
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    // ─── Boot ─────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $quote) {
            $quote->reference = 'DV-'.strtoupper(uniqid());
        });
    }
}
