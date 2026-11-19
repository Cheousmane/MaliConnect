<?php

namespace App\Models;

use Database\Factories\AdminAuditLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Trace d'une action sensible réalisée dans l'administration.
 */
class AdminAuditLog extends Model
{
    /** @use HasFactory<AdminAuditLogFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    /**
     * Administrateur à l'origine de l'action.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Ressource concernée par l'action.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Libellé lisible de l'action.
     */
    public function actionLabel(): string
    {
        return match ($this->action) {
            'booking.status.updated' => 'Statut de réservation mis à jour',
            'business.status.updated' => 'Statut d’entreprise mis à jour',
            'category.activation.updated' => 'Activation de catégorie modifiée',
            'category.created' => 'Catégorie créée',
            'category.updated' => 'Catégorie mise à jour',
            'payment.approved' => 'Paiement validé',
            'payment.rejected' => 'Paiement refusé',
            'plan.activation.updated' => 'Activation de formule modifiée',
            'plan.created' => 'Formule créée',
            'plan.updated' => 'Formule mise à jour',
            'provider_profile.approved' => 'Profil professionnel approuvé',
            'provider_profile.refused' => 'Profil professionnel refusé',
            'provider_profile.activation.updated' => 'Activation de profil modifiée',
            'report.status.updated' => 'Statut de signalement mis à jour',
            'review.approved' => 'Avis publié',
            'review.rejected' => 'Avis refusé',
            'service_request.status.updated' => 'Statut de demande mis à jour',
            'service.activation.updated' => 'Activation de service modifiée',
            'subcategory.activation.updated' => 'Activation de sous-catégorie modifiée',
            'subcategory.created' => 'Sous-catégorie créée',
            'subcategory.updated' => 'Sous-catégorie mise à jour',
            'subscription.cancelled' => 'Abonnement résilié',
            'subscription.extended' => 'Abonnement prolongé',
            'subscription.reactivated' => 'Abonnement réactivé',
            'subscription.suspended' => 'Abonnement suspendu',
            'user.roles.updated' => 'Rôles mis à jour',
            'user.status.updated' => 'Statut de compte mis à jour',
            default => ucfirst(str_replace(['.', '_'], ' ', $this->action)),
        };
    }
}
