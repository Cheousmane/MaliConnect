<?php

use App\Models\ProviderProfile;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * Tests de la gestion des services proposés par un professionnel.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

/**
 * Crée un compte professionnel avec un profil.
 *
 * @return array{0: User, 1: ProviderProfile}
 */
function professionalWithProfile(): array
{
    $user = User::factory()->create();
    $user->assignRole('professional');

    $profile = ProviderProfile::factory()->forUser($user)->create();

    return [$user, $profile];
}

test('un professionnel peut ajouter un service', function () {
    [$user, $profile] = professionalWithProfile();

    $response = $this->actingAs($user)->post("/pro/profiles/{$profile->id}/services", [
        'name' => 'Pose de meuble',
        'description' => 'Dépose et installation.',
        'price_type' => 'fixed',
        'price_min' => 25000,
        'duration_minutes' => 120,
    ]);

    $response->assertSessionHasNoErrors();

    $service = $profile->services()->firstOrFail();

    expect($service->name)->toBe('Pose de meuble');
    expect((float) $service->price_min)->toBe(25000.0);
    expect($service->duration_minutes)->toBe(120);
    expect($service->is_active)->toBeTrue();
});

test('un service peut être modifié', function () {
    [$user, $profile] = professionalWithProfile();
    $service = $profile->services()->create(['name' => 'Ancien nom', 'price_type' => 'quote']);

    $this->actingAs($user)
        ->patch("/pro/profiles/{$profile->id}/services/{$service->id}", [
            'name' => 'Nouveau nom',
            'description' => 'Description mise à jour.',
            'price_type' => 'range',
            'price_min' => 10000,
            'price_max' => 30000,
            'duration_minutes' => 60,
            'is_active' => true,
        ])
        ->assertSessionHasNoErrors();

    $service->refresh();

    expect($service->name)->toBe('Nouveau nom');
    expect((float) $service->price_max)->toBe(30000.0);
    expect($service->duration_minutes)->toBe(60);
});

test('un service peut être désactivé puis réactivé', function () {
    [$user, $profile] = professionalWithProfile();
    $service = $profile->services()->create(['name' => 'Prestation', 'price_type' => 'quote', 'is_active' => true]);

    $this->actingAs($user)
        ->patch("/pro/profiles/{$profile->id}/services/{$service->id}", [
            'name' => 'Prestation',
            'price_type' => 'quote',
            'is_active' => false,
        ])
        ->assertSessionHasNoErrors();

    expect($service->refresh()->is_active)->toBeFalse();

    $this->actingAs($user)
        ->patch("/pro/profiles/{$profile->id}/services/{$service->id}", [
            'name' => 'Prestation',
            'price_type' => 'quote',
            'is_active' => true,
        ])
        ->assertSessionHasNoErrors();

    expect($service->refresh()->is_active)->toBeTrue();
});

test('un service peut être supprimé', function () {
    [$user, $profile] = professionalWithProfile();
    $service = $profile->services()->create(['name' => 'Prestation', 'price_type' => 'quote']);

    $this->actingAs($user)
        ->delete("/pro/profiles/{$profile->id}/services/{$service->id}")
        ->assertSessionHasNoErrors();

    expect($profile->services()->count())->toBe(0);
});

test('une fourchette de prix incohérente est refusée', function () {
    [$user, $profile] = professionalWithProfile();

    $this->actingAs($user)
        ->post("/pro/profiles/{$profile->id}/services", [
            'name' => 'Prestation',
            'price_type' => 'range',
            'price_min' => 30000,
            'price_max' => 10000,
        ])
        ->assertSessionHasErrors('price_max');

    expect($profile->services()->count())->toBe(0);
});

test('un prix est obligatoire pour un tarif fixe', function () {
    [$user, $profile] = professionalWithProfile();

    $this->actingAs($user)
        ->post("/pro/profiles/{$profile->id}/services", [
            'name' => 'Prestation',
            'price_type' => 'fixed',
        ])
        ->assertSessionHasErrors('price_min');
});

test('un client ne peut pas gérer les services', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $profile = ProviderProfile::factory()->create();

    $this->actingAs($customer)
        ->post("/pro/profiles/{$profile->id}/services", [
            'name' => 'Prestation',
            'price_type' => 'quote',
        ])
        ->assertForbidden();
});

test('un professionnel ne peut pas gérer les services d un autre profil', function () {
    $user = User::factory()->create();
    $user->assignRole('professional');

    $profile = ProviderProfile::factory()->create();

    $this->actingAs($user)
        ->post("/pro/profiles/{$profile->id}/services", [
            'name' => 'Prestation',
            'price_type' => 'quote',
        ])
        ->assertForbidden();
});
