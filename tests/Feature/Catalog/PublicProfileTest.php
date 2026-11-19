<?php

use App\Enums\AccountStatus;
use App\Enums\BusinessStatus;
use App\Models\Business;
use App\Models\ProviderProfile;
use App\Models\Service;

/**
 * Tests des pages publiques : profil d'un professionnel et détail d'un service.
 */
test('le profil public d un professionnel vérifié est accessible', function () {
    $profile = ProviderProfile::factory()->verified()->create([
        'business_name' => 'Atelier Koné',
        'description' => 'Menuiserie sur mesure.',
    ]);

    Service::factory()->create([
        'provider_profile_id' => $profile->id,
        'name' => 'Pose de meuble',
        'price_type' => 'fixed',
        'price_min' => 25000,
    ]);

    $response = $this->get("/professionnels/{$profile->slug}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('public/Professional')
        ->where('profile.business_name', 'Atelier Koné')
        ->has('profile.services', 1),
    );
});

test('un profil en attente n est pas public', function () {
    $profile = ProviderProfile::factory()->pending()->create();

    $this->get("/professionnels/{$profile->slug}")->assertNotFound();
});

test('un profil refusé n est pas public', function () {
    $profile = ProviderProfile::factory()->refused()->create();

    $this->get("/professionnels/{$profile->slug}")->assertNotFound();
});

test('un profil désactivé n est plus public', function () {
    $profile = ProviderProfile::factory()->verified()->create(['is_active' => false]);

    $this->get("/professionnels/{$profile->slug}")->assertNotFound();
});

test('le profil d un compte suspendu n est plus public', function () {
    $profile = ProviderProfile::factory()->verified()->create();

    $profile->user->forceFill(['status' => AccountStatus::Suspended])->save();

    $this->get("/professionnels/{$profile->slug}")->assertNotFound();
});

test('le profil d une entreprise suspendue n est plus public', function () {
    $business = Business::factory()->verified()->create();
    $profile = ProviderProfile::factory()->verified()->create(['business_id' => $business->id]);

    $business->forceFill(['status' => BusinessStatus::Suspended])->save();

    $this->get("/professionnels/{$profile->slug}")->assertNotFound();
});

test('le détail d un service est accessible', function () {
    $profile = ProviderProfile::factory()->verified()->create();
    $service = Service::factory()->create([
        'provider_profile_id' => $profile->id,
        'name' => 'Dépannage',
        'price_type' => 'fixed',
        'price_min' => 10000,
    ]);

    $response = $this->get("/services/{$service->id}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('public/Service')
        ->where('service.name', 'Dépannage')
        ->where('professional.id', $profile->id),
    );
});

test('le service d un profil non vérifié n est pas public', function () {
    $profile = ProviderProfile::factory()->pending()->create();
    $service = Service::factory()->create(['provider_profile_id' => $profile->id]);

    $this->get("/services/{$service->id}")->assertNotFound();
});

test('un service désactivé n est plus public', function () {
    $profile = ProviderProfile::factory()->verified()->create();
    $service = Service::factory()->create([
        'provider_profile_id' => $profile->id,
        'is_active' => false,
    ]);

    $this->get("/services/{$service->id}")->assertNotFound();
});
