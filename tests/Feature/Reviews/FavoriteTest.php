<?php

use App\Models\Favorite;
use App\Models\ProviderProfile;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Tests des favoris : ajout, retrait, liste et absence de doublons.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

/**
 * Crée un compte client.
 */
function favoriteTestUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('customer');

    return $user;
}

/**
 * Crée un profil professionnel public.
 */
function favoriteTestProfile(): ProviderProfile
{
    $owner = User::factory()->create();
    $owner->assignRole('professional');

    return ProviderProfile::factory()->forUser($owner)->verified()->create();
}

test('un utilisateur ajoute un professionnel à ses favoris', function () {
    $user = favoriteTestUser();
    $profile = favoriteTestProfile();

    $this->actingAs($user)
        ->post("/favoris/{$profile->id}")
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Favorite::count())->toBe(1)
        ->and($user->favorites()->first()->provider_profile_id)->toBe($profile->id);
});

test('ajouter deux fois le même professionnel ne crée pas de doublon', function () {
    $user = favoriteTestUser();
    $profile = favoriteTestProfile();

    $this->actingAs($user)->post("/favoris/{$profile->id}")->assertSessionHasNoErrors();
    $this->actingAs($user)->post("/favoris/{$profile->id}")->assertSessionHasNoErrors();

    expect(Favorite::count())->toBe(1);
});

test('un utilisateur retire un professionnel de ses favoris', function () {
    $user = favoriteTestUser();
    $profile = favoriteTestProfile();

    Favorite::factory()->create([
        'user_id' => $user->id,
        'provider_profile_id' => $profile->id,
    ]);

    $this->actingAs($user)
        ->delete("/favoris/{$profile->id}")
        ->assertSessionHasNoErrors();

    expect(Favorite::count())->toBe(0);
});

test('la liste des favoris ne contient que ceux du compte connecté', function () {
    $user = favoriteTestUser();
    $other = favoriteTestUser();
    $profile = favoriteTestProfile();
    $otherProfile = favoriteTestProfile();

    Favorite::factory()->create(['user_id' => $user->id, 'provider_profile_id' => $profile->id]);
    Favorite::factory()->create(['user_id' => $other->id, 'provider_profile_id' => $otherProfile->id]);

    $this->actingAs($user)
        ->get('/favoris')
        ->assertInertia(fn (Assert $page) => $page
            ->component('favorites/Index')
            ->has('favorites.data', 1)
            ->where('favorites.data.0.profile.id', $profile->id)
        );
});

test('un profil non vérifié ne peut pas être ajouté aux favoris', function () {
    $user = favoriteTestUser();
    $profile = ProviderProfile::factory()->pending()->create();

    $this->actingAs($user)
        ->post("/favoris/{$profile->id}")
        ->assertNotFound();

    expect(Favorite::count())->toBe(0);
});

test('un visiteur ne peut pas gérer de favoris', function () {
    $profile = favoriteTestProfile();

    $this->post("/favoris/{$profile->id}")->assertRedirect(route('login'));
    $this->get('/favoris')->assertRedirect(route('login'));

    expect(Favorite::count())->toBe(0);
});
