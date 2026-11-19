<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Support\Identity\PermissionCatalogue;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Crée le catalogue de permissions granulaires et les rôles applicatifs.
 *
 * Les droits ne sont jamais déduits du seul nom du rôle : chaque rôle reçoit
 * une liste explicite de permissions (voir PermissionCatalogue).
 */
class RoleSeeder extends Seeder
{
    /**
     * Exécute le seeder.
     */
    public function run(): void
    {
        // Réinitialiser le cache des permissions.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Création des permissions granulaires.
        foreach (PermissionCatalogue::all() as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Création des rôles et attribution de leurs permissions.
        foreach (UserRole::cases() as $role) {
            $model = Role::firstOrCreate(['name' => $role->value, 'guard_name' => 'web']);

            $model->syncPermissions(PermissionCatalogue::forRole($role->value));
        }

        $this->command?->info('Rôles et permissions créés : super-admin, admin, customer, professional, company-manager.');
    }
}
