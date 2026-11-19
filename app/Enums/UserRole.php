<?php

namespace App\Enums;

/**
 * Rôles applicatifs gérés par Spatie Laravel Permission.
 *
 * Les droits effectifs ne sont pas déduits du rôle mais des permissions
 * granulaires qui lui sont associées (voir RoleSeeder).
 */
enum UserRole: string
{
    case SuperAdmin = 'super-admin';

    case Admin = 'admin';

    case Customer = 'customer';

    case Professional = 'professional';

    case CompanyManager = 'company-manager';

    /**
     * Libellé affiché dans l'interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super administrateur',
            self::Admin => 'Administrateur',
            self::Customer => 'Client',
            self::Professional => 'Professionnel',
            self::CompanyManager => 'Gestionnaire d’entreprise',
        };
    }

    /**
     * Indique si un administrateur (rôle « admin ») peut attribuer ce rôle.
     *
     * Les rôles d'administration restent réservés au super administrateur afin
     * d'éviter toute auto-élévation de privilèges.
     */
    public function isAssignableByAdmin(): bool
    {
        return $this !== self::SuperAdmin && $this !== self::Admin;
    }
}
