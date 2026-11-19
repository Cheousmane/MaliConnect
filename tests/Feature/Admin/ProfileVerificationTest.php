<?php

use App\Enums\ProfileStatus;
use App\Models\ProviderProfile;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * Tests de la vérification administrative des profils professionnels.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

/**
 * Crée un compte d'administration.
 */
function administrator(string $role = 'admin'): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('un administrateur consulte la file de vérification', function () {
    $admin = administrator();
    ProviderProfile::factory()->pending()->create(['business_name' => 'Atelier Koné']);

    $response = $this->actingAs($admin)->get('/admin/provider-profiles');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/ProviderProfiles')
        ->where('profiles.total', 1)
        ->where('profiles.data.0.business_name', 'Atelier Koné'),
    );
});

test('un client ne peut pas consulter la file de vérification', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $this->actingAs($user)->get('/admin/provider-profiles')->assertForbidden();
});

test('un administrateur peut approuver un profil', function () {
    $admin = administrator();
    $profile = ProviderProfile::factory()->pending()->create();

    $this->actingAs($admin)
        ->post("/admin/provider-profiles/{$profile->id}/review", ['decision' => 'approve'])
        ->assertSessionHasNoErrors();

    $profile->refresh();

    expect($profile->status)->toBe(ProfileStatus::Verified);
    expect($profile->verified_by)->toBe($admin->id);
    expect($profile->verified_at)->not->toBeNull();
    expect($profile->isPubliclyVisible())->toBeTrue();
});

test('un refus exige un motif', function () {
    $admin = administrator();
    $profile = ProviderProfile::factory()->pending()->create();

    $this->actingAs($admin)
        ->post("/admin/provider-profiles/{$profile->id}/review", ['decision' => 'refuse'])
        ->assertSessionHasErrors('reason');

    expect($profile->refresh()->status)->toBe(ProfileStatus::Pending);
});

test('un administrateur peut refuser un profil avec un motif', function () {
    $admin = administrator();
    $profile = ProviderProfile::factory()->pending()->create();

    $this->actingAs($admin)
        ->post("/admin/provider-profiles/{$profile->id}/review", [
            'decision' => 'refuse',
            'reason' => 'Pièces justificatives manquantes.',
        ])
        ->assertSessionHasNoErrors();

    $profile->refresh();

    expect($profile->status)->toBe(ProfileStatus::Refused);
    expect($profile->refusal_reason)->toBe('Pièces justificatives manquantes.');
    expect($profile->isPubliclyVisible())->toBeFalse();
});

test('un administrateur peut suspendre un profil vérifié', function () {
    $admin = administrator();
    $profile = ProviderProfile::factory()->verified()->create();

    $this->actingAs($admin)
        ->post("/admin/provider-profiles/{$profile->id}/review", [
            'decision' => 'suspend',
            'reason' => 'Signalement de clients.',
        ])
        ->assertSessionHasNoErrors();

    $profile->refresh();

    expect($profile->status)->toBe(ProfileStatus::Suspended);
    expect($profile->isPubliclyVisible())->toBeFalse();
});

test('une décision inconnue est rejetée', function () {
    $admin = administrator();
    $profile = ProviderProfile::factory()->pending()->create();

    $this->actingAs($admin)
        ->post("/admin/provider-profiles/{$profile->id}/review", ['decision' => 'inconnu'])
        ->assertSessionHasErrors('decision');

    expect($profile->refresh()->status)->toBe(ProfileStatus::Pending);
});

test('un profil vérifié devient visible dans le catalogue public', function () {
    $admin = administrator();
    $profile = ProviderProfile::factory()->pending()->create();

    expect(ProviderProfile::publiclyVisible()->count())->toBe(0);

    $this->actingAs($admin)->post("/admin/provider-profiles/{$profile->id}/review", ['decision' => 'approve']);

    expect(ProviderProfile::publiclyVisible()->count())->toBe(1);
});
