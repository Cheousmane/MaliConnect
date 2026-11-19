<?php

namespace App\Enums;

/**
 * Statuts de vérification administrative d'un profil professionnel.
 *
 * Seul le statut « verified » autorise une présentation publique comme
 * profil vérifié.
 */
enum ProfileStatus: string
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
            self::Verified => 'Vérifié',
            self::Refused => 'Refusé',
            self::Suspended => 'Suspendu',
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
     * Indique si le profil peut être présenté publiquement comme vérifié.
     */
    public function isPubliclyVisible(): bool
    {
        return $this === self::Verified;
    }
}
