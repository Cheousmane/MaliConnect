<?php

namespace App\Enums;

/**
 * Rôles d'un collaborateur au sein d'une entreprise.
 */
enum BusinessMemberRole: string
{
    case Owner = 'owner';

    case Manager = 'manager';

    case Agent = 'agent';

    /**
     * Libellé affiché dans l'interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Responsable',
            self::Manager => 'Gestionnaire',
            self::Agent => 'Collaborateur',
        };
    }

    /**
     * Indique si ce rôle peut gérer les profils de l'entreprise.
     */
    public function canManageProfiles(): bool
    {
        return $this !== self::Agent;
    }

    /**
     * Indique si ce rôle peut gérer les collaborateurs de l'entreprise.
     */
    public function canManageMembers(): bool
    {
        return $this === self::Owner;
    }

    /**
     * Niveau hiérarchique du rôle (plus élevé, plus powerful).
     */
    public function rank(): int
    {
        return match ($this) {
            self::Owner => 3,
            self::Manager => 2,
            self::Agent => 1,
        };
    }
}
