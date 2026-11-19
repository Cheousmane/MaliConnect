<?php

namespace App\Services\Identity;

use App\Models\User;
use App\Support\Identity\PermissionCatalogue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Prépare les rôles et permissions nécessaires au module d'identité.
 *
 * Les rôles sont créés à la demande afin que l'inscription fonctionne
 * même si le catalogue n'a pas encore été seedé.
 */
class IdentityProvisioner
{
    /**
     * Crée le rôle et ses permissions s'ils sont absents.
     */
    public static function ensureRole(string $role, array $permissions = []): Role
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $model = Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $model->syncPermissions($permissions);

        return $model;
    }

    /**
     * Attribue à l'utilisateur le rôle par défaut d'un compte particulier.
     *
     * Aucun droit d'administration n'est accordé à ce stade.
     */
    public static function assignDefaultRole(User $user): void
    {
        // Le rôle n'est provisionné qu'une fois : les permissions sont
        // resynchronisées par le seeder lors des mises à jour du catalogue.
        $exists = Role::query()
            ->where('name', 'customer')
            ->where('guard_name', 'web')
            ->exists();

        if (! $exists) {
            self::ensureRole('customer', PermissionCatalogue::forRole('customer'));
        }

        $user->assignRole('customer');
    }
}
