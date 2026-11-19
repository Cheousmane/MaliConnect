<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'professional_id',
        'booking_id',
        'customer_id',
        'rating',
        'comment',
        'is_approved',
        'professional_reply',
        'replied_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_approved' => 'boolean',
            'replied_at' => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeForProfessional($query, int $professionalId)
    {
        return $query->where('professional_id', $professionalId);
    }
}
