<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Événement immuable de l'historique d'un abonnement.
 */
class SubscriptionHistory extends Model
{
    protected $fillable = [
        'subscription_id',
        'actor_id',
        'event',
        'from_status',
        'to_status',
        'from_plan_id',
        'to_plan_id',
        'billing_cycle',
        'amount',
        'currency',
        'starts_at',
        'ends_at',
        'note',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:0',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function fromPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'from_plan_id');
    }

    public function toPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'to_plan_id');
    }

    // ─── Helpers ──────────────────────────────────────────────

    /**
     * Libellé français de l'événement.
     */
    public function eventLabel(): string
    {
        return match ($this->event) {
            'created' => 'Abonnement créé',
            'activated' => 'Abonnement activé',
            'renewed' => 'Abonnement renouvelé',
            'upgraded' => 'Formule améliorée',
            'downgraded' => 'Formule rétrogradée',
            'plan_changed' => 'Changement de formule',
            'plan_scheduled' => 'Changement de formule programmé',
            'cancelled' => 'Abonnement résilié',
            'expired' => 'Abonnement expiré',
            'suspended' => 'Abonnement suspendu',
            'reactivated' => 'Abonnement réactivé',
            'extended' => 'Échéance prolongée',
            'past_due' => 'Paiement en retard',
            'trial_started' => 'Essai gratuit démarré',
            'payment_rejected' => 'Paiement refusé',
            default => ucfirst(str_replace('_', ' ', $this->event)),
        };
    }
}
