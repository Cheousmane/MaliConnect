<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Database\Factories\ProviderReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Avis d'un client sur une prestation terminée.
 *
 * L'avis reste privé tant qu'il n'a pas été approuvé par l'administration.
 * Il peut ensuite recevoir une réponse publique du professionnel.
 */
class ProviderReview extends Model
{
    /** @use HasFactory<ProviderReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'provider_booking_id',
        'customer_id',
        'provider_profile_id',
        'rating',
        'comment',
        'status',
        'published_at',
        'professional_reply',
        'replied_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => ReviewStatus::class,
            'published_at' => 'datetime',
            'replied_at' => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    /**
     * Réservation ayant donné lieu à l'avis.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(ProviderBooking::class, 'provider_booking_id');
    }

    /**
     * Client auteur de l'avis.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Profil professionnel évalué.
     */
    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    // ─── Helpers ──────────────────────────────────────────────

    /**
     * Indique si l'avis est visible publiquement.
     */
    public function isApproved(): bool
    {
        return $this->status === ReviewStatus::Approved;
    }

    /**
     * Indique si l'avis attend une décision de modération.
     */
    public function isPending(): bool
    {
        return $this->status === ReviewStatus::Pending;
    }

    /**
     * Indique si le professionnel peut encore répondre à l'avis.
     */
    public function canBeReplied(): bool
    {
        return $this->isApproved() && $this->professional_reply === null;
    }

    // ─── Scopes ───────────────────────────────────────────────

    /**
     * Limite aux avis approuvés et publiés.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', ReviewStatus::Approved);
    }

    /**
     * Limite aux avis en attente de modération.
     */
    public function scopePending($query)
    {
        return $query->where('status', ReviewStatus::Pending);
    }
}
