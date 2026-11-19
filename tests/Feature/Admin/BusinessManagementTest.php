<?php

use App\Enums\BusinessStatus;
use App\Models\AdminAuditLog;
use App\Models\Business;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * Vérifie la gestion administrative des entreprises.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

/**
 * Crée un administrateur de test.
 */
function businessAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

test('un administrateur consulte la liste des entreprises', function () {
    $admin = businessAdmin();
    Business::factory()->create(['name' => 'Koné Services']);

    $this->actingAs($admin)->get('/admin/businesses')->assertInertia(fn ($page) => $page
        ->component('admin/Businesses')
        ->where('businesses.total', 1)
        ->where('businesses.data.0.name', 'Koné Services'),
    );
});

test('un client ne peut pas gérer les entreprises', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $this->actingAs($customer)->get('/admin/businesses')->assertForbidden();
});

test('un administrateur peut vérifier une entreprise', function () {
    $admin = businessAdmin();
    $business = Business::factory()->pending()->create();

    $this->actingAs($admin)
        ->patch("/admin/businesses/{$business->id}/status", ['decision' => 'verify'])
        ->assertSessionHasNoErrors();

    $business->refresh();

    expect($business->status)->toBe(BusinessStatus::Verified);
    expect($business->verified_by)->toBe($admin->id);
    expect(AdminAuditLog::query()->where('action', 'business.status.updated')->count())->toBe(1);
});

test('un refus d’entreprise exige un motif', function () {
    $admin = businessAdmin();
    $business = Business::factory()->pending()->create();

    $this->actingAs($admin)
        ->patch("/admin/businesses/{$business->id}/status", ['decision' => 'refuse'])
        ->assertSessionHasErrors('reason');

    expect($business->refresh()->status)->toBe(BusinessStatus::Pending);
});

test('un administrateur peut suspendre puis réactiver une entreprise', function () {
    $admin = businessAdmin();
    $business = Business::factory()->verified()->create();

    $this->actingAs($admin)
        ->patch("/admin/businesses/{$business->id}/status", [
            'decision' => 'suspend',
            'reason' => 'Documents devenus invalides.',
        ])
        ->assertSessionHasNoErrors();

    expect($business->refresh()->status)->toBe(BusinessStatus::Suspended);

    $this->actingAs($admin)
        ->patch("/admin/businesses/{$business->id}/status", ['decision' => 'activate'])
        ->assertSessionHasNoErrors();

    expect($business->refresh()->status)->toBe(BusinessStatus::Verified);
});
