<?php

namespace App\Enums;

/**
 * Statuts de vérification administrative d'une entreprise.
 */
enum BusinessStatus: string
{
    case Pending = 'pending';

    case Verified = 'verified';

    case Refused = 'refused';

    case Suspended = 'suspended';

    /**
     * Libellé affiché dans l'interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Verified => 'Vérifiée',
            self::Refused => 'Refusée',
            self::Suspended => 'Suspendue',
        };
    }

    /**
     * Ton visuel associé au statut dans l'interface.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Verified => 'success',
            self::Refused => 'danger',
            self::Suspended => 'neutral',
        };
    }

    /**
     * Indique si l'entreprise a été vérifiée par l'administration.
     */
    public function isVerified(): bool
    {
        return $this === self::Verified;
    }
}
