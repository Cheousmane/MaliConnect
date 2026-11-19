<?php

namespace App\Enums;

/**
 * Statuts du cycle de vie d'un abonnement professionnel.
 */
enum SubscriptionStatus: string
{
    case Pending = 'pending';

    case Trialing = 'trialing';

    case Active = 'active';

    case PastDue = 'past_due';

    case Expired = 'expired';

    case Cancelled = 'cancelled';

    case Suspended = 'suspended';

    /**
     * Libellé affiché dans l'interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Trialing => 'En essai',
            self::Active => 'Actif',
            self::PastDue => 'Paiement en retard',
            self::Expired => 'Expiré',
            self::Cancelled => 'Résilié',
            self::Suspended => 'Suspendu',
        };
    }

    /**
     * Ton visuel associé au statut.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Trialing => 'info',
            self::Active => 'success',
            self::PastDue => 'warning',
            self::Expired => 'neutral',
            self::Cancelled => 'danger',
            self::Suspended => 'danger',
        };
    }

    /**
     * Indique si l'abonnement est actif ou en période d'essai.
     */
    public function isActive(): bool
    {
        return in_array($this, [self::Active, self::Trialing], true);
    }

    /**
     * Indique si le statut ouvre encore droit aux fonctionnalités du plan.
     *
     * Le statut « past_due » reste couvert par la période de grâce : le
     * service d'entitlements vérifie en plus la date de fin de grâce.
     */
    public function grantsAccess(): bool
    {
        return in_array($this, [self::Active, self::Trialing, self::PastDue], true);
    }

    /**
     * Indique si l'abonnement est définitivement terminé.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Expired, self::Cancelled], true);
    }
}
