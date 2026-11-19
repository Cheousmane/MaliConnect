<?php

namespace App\Enums;

/**
 * Statuts de modération d'un avis.
 *
 * Seul un avis approuvé est visible publiquement et entre dans la note
 * moyenne du profil professionnel.
 */
enum ReviewStatus: string
{
    case Pending = 'pending';

    case Approved = 'approved';

    case Rejected = 'rejected';

    /**
     * Libellé affiché dans l'interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En modération',
            self::Approved => 'Publié',
            self::Rejected => 'Refusé',
        };
    }

    /**
     * Ton visuel associé au statut dans l'interface.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }

    /**
     * Indique si l'avis est visible publiquement.
     */
    public function isPublic(): bool
    {
        return $this === self::Approved;
    }
}
