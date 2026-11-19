<?php

namespace App\Enums;

/**
 * Statuts possibles d'un compte utilisateur.
 *
 * Le statut conditionne l'accès à l'application : un compte suspendu ou
 * clôturé ne peut plus se connecter ni accéder aux pages authentifiées.
 */
enum AccountStatus: string
{
    case Active = 'active';

    case Suspended = 'suspended';

    case Closed = 'closed';

    /**
     * Libellé affiché dans l'interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Actif',
            self::Suspended => 'Suspendu',
            self::Closed => 'Clôturé',
        };
    }

    /**
     * Ton visuel associé au statut dans l'interface.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Suspended => 'warning',
            self::Closed => 'neutral',
        };
    }

    /**
     * Indique si le statut autorise la connexion et l'accès à l'application.
     */
    public function allowsAccess(): bool
    {
        return $this === self::Active;
    }
}
