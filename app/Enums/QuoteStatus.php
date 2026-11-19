<?php

namespace App\Enums;

/**
 * Statuts d'un devis envoyé par un professionnel.
 */
enum QuoteStatus: string
{
    case Pending = 'pending';

    case Accepted = 'accepted';

    case Rejected = 'rejected';

    case Expired = 'expired';

    /**
     * Libellé affiché dans l'interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Accepted => 'Accepté',
            self::Rejected => 'Refusé',
            self::Expired => 'Expiré',
        };
    }

    /**
     * Ton visuel associé au statut dans l'interface.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Accepted => 'success',
            self::Rejected => 'danger',
            self::Expired => 'neutral',
        };
    }
}
