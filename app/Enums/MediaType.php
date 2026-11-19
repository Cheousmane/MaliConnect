<?php

namespace App\Enums;

/**
 * Types de médias associés à un profil professionnel.
 */
enum MediaType: string
{
    case Photo = 'photo';

    case Realisation = 'realisation';

    case Cover = 'cover';

    /**
     * Libellé affiché dans l'interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Photo => 'Photo',
            self::Realisation => 'Réalisation',
            self::Cover => 'Couverture',
        };
    }
}
