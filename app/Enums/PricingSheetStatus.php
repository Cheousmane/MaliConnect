<?php

namespace App\Enums;

/**
 * Statuts d'une fiche tarifaire professionnelle.
 *
 * Seules les fiches publiées et non expirées sont visibles publiquement.
 */
enum PricingSheetStatus: string
{
    case Draft = 'draft';

    case Published = 'published';

    case Archived = 'archived';

    /**
     * Libellé affiché dans l'interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Published => 'Publiée',
            self::Archived => 'Archivée',
        };
    }

    /**
     * Ton visuel associé au statut.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'warning',
            self::Published => 'success',
            self::Archived => 'neutral',
        };
    }

    /**
     * Indique si la fiche est visible par les visiteurs.
     */
    public function isPublic(): bool
    {
        return $this === self::Published;
    }
}
