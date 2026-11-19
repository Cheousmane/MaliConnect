<?php

use App\Enums\ProfileStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Category;
use App\Models\ProviderProfile;
use App\Models\ProviderProfileMedia;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;

/**
 * Tests du module de gestion des profils professionnels.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

/**
 * Crée un compte professionnel.
 */
function professional(): User
{
    $user = User::factory()->create();
    $user->assignRole('professional');

    return $user;
}

test('un professionnel peut créer un profil', function () {
    $user = professional();
    $category = Category::factory()->create();

    $response = $this->actingAs($user)->post('/pro/profiles', [
        'business_name' => 'Atelier Koné',
        'category_id' => $category->id,
        'description' => 'Menuiserie et pose de meuble.',
        'phone' => '76123456',
        'whatsapp' => '70123456',
        'city' => 'Bamako',
        'commune' => 'Commune IV',
        'quartier' => 'Badalabougou',
    ]);

    $response->assertSessionHasNoErrors();

    $profile = ProviderProfile::where('user_id', $user->id)->firstOrFail();

    expect($profile->business_name)->toBe('Atelier Koné');
    expect($profile->slug)->toBe('atelier-kone');
    expect($profile->status)->toBe(ProfileStatus::Pending);
    expect($profile->submitted_at)->not->toBeNull();
    expect($profile->phone)->toBe('+22376123456');
    expect($profile->whatsapp)->toBe('+22370123456');

    // Une zone d'intervention principale et sept horaires sont créés.
    expect($profile->serviceAreas)->toHaveCount(1);
    expect($profile->serviceAreas->first()->is_primary)->toBeTrue();
    expect($profile->hours)->toHaveCount(7);
});

test('un profil sans nom commercial ni catégorie est refusé', function () {
    $user = professional();

    $response = $this->actingAs($user)->post('/pro/profiles', []);

    $response->assertSessionHasErrors(['business_name', 'category_id']);

    expect(ProviderProfile::count())->toBe(0);
});

test('un client ne peut pas créer de profil professionnel', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $this->actingAs($user)
        ->post('/pro/profiles', [
            'business_name' => 'Atelier Koné',
            'category_id' => Category::factory()->create()->id,
            'city' => 'Bamako',
        ])
        ->assertForbidden();

    expect(ProviderProfile::count())->toBe(0);
});

test('un professionnel peut modifier son profil', function () {
    $user = professional();
    $profile = ProviderProfile::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->patch("/pro/profiles/{$profile->id}", [
        'business_name' => 'Atelier Koné & Fils',
        'category_id' => $profile->category_id,
        'city' => 'Bamako',
        'commune' => 'Commune V',
    ]);

    $response->assertSessionHasNoErrors();

    $profile->refresh();

    expect($profile->business_name)->toBe('Atelier Koné & Fils');
    expect($profile->slug)->toBe('atelier-kone-fils');
    expect($profile->commune)->toBe('Commune V');
});

test('un professionnel ne peut pas modifier le profil d autrui', function () {
    $owner = professional();
    $other = professional();
    $profile = ProviderProfile::factory()->forUser($owner)->create();

    $this->actingAs($other)
        ->patch("/pro/profiles/{$profile->id}", [
            'business_name' => 'Profil volé',
            'category_id' => $profile->category_id,
            'city' => 'Bamako',
        ])
        ->assertForbidden();

    expect($profile->refresh()->business_name)->not->toBe('Profil volé');
});

test('un profil peut être soumis à la vérification', function () {
    $user = professional();
    $profile = ProviderProfile::factory()->forUser($user)->refused()->create();

    $this->actingAs($user)
        ->post("/pro/profiles/{$profile->id}/submit")
        ->assertRedirect();

    $profile->refresh();

    expect($profile->status)->toBe(ProfileStatus::Pending);
    expect($profile->refusal_reason)->toBeNull();
    expect($profile->submitted_at)->not->toBeNull();
});

test('un profil peut être désactivé par son propriétaire', function () {
    $user = professional();
    $profile = ProviderProfile::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->post("/pro/profiles/{$profile->id}/deactivate")
        ->assertRedirect();

    expect($profile->refresh()->is_active)->toBeFalse();
});

test('les zones d intervention peuvent être remplacées', function () {
    $user = professional();
    $profile = ProviderProfile::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->put("/pro/profiles/{$profile->id}/areas", [
            'areas' => [
                ['city' => 'Bamako', 'commune' => 'Commune IV', 'quartier' => 'Badalabougou'],
                ['city' => 'Koulikoro', 'commune' => null, 'quartier' => null],
            ],
        ])
        ->assertSessionHasNoErrors();

    expect($profile->serviceAreas()->count())->toBe(2);
    expect($profile->serviceAreas()->where('city', 'Koulikoro')->exists())->toBeTrue();
});

test('les horaires peuvent être mis à jour', function () {
    $user = professional();
    $profile = ProviderProfile::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->put("/pro/profiles/{$profile->id}/schedule", [
            'hours' => [
                ['day_of_week' => 1, 'opens_at' => '07:00', 'closes_at' => '17:00', 'is_closed' => false],
            ],
            'availabilities' => [
                ['date' => now()->addWeek()->toDateString(), 'starts_at' => '09:00', 'ends_at' => '12:00'],
            ],
        ])
        ->assertSessionHasNoErrors();

    expect($profile->hours()->count())->toBe(1);
    expect($profile->hours()->first()->opens_at)->toStartWith('07:00');
    expect($profile->availabilities()->count())->toBe(1);
});

test('un média peut être ajouté au profil', function () {
    $user = professional();
    $profile = ProviderProfile::factory()->forUser($user)->create();

    Subscription::factory()->create([
        'provider_profile_id' => $profile->id,
        'plan' => 'pro',
        'status' => SubscriptionStatus::Active,
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
        'amount' => 15000,
        'currency' => 'XOF',
    ]);

    $this->actingAs($user)
        ->post("/pro/profiles/{$profile->id}/media", [
            'media' => UploadedFile::fake()->image('realisation.jpg'),
            'type' => 'realisation',
            'caption' => 'Chantier terminé',
        ])
        ->assertSessionHasNoErrors();

    expect($profile->media()->count())->toBe(1);
    expect(ProviderProfileMedia::first()->caption)->toBe('Chantier terminé');
});

test('un service peut être ajouté au profil', function () {
    $user = professional();
    $profile = ProviderProfile::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->post("/pro/profiles/{$profile->id}/services", [
            'name' => 'Pose de meuble',
            'price_type' => 'quote',
        ])
        ->assertSessionHasNoErrors();

    expect($profile->services()->count())->toBe(1);
    expect($profile->services()->first()->provider_profile_id)->toBe($profile->id);
});

test('un profil en attente n est jamais présenté comme vérifié', function () {
    $pending = ProviderProfile::factory()->pending()->create();
    $verified = ProviderProfile::factory()->verified()->create();

    expect($pending->isVerified())->toBeFalse();
    expect($pending->isPubliclyVisible())->toBeFalse();
    expect($verified->isPubliclyVisible())->toBeTrue();

    $visible = ProviderProfile::publiclyVisible()->pluck('id');

    expect($visible->contains($verified->id))->toBeTrue();
    expect($visible->contains($pending->id))->toBeFalse();
});

test('les profils sont reliés à leur compte', function () {
    $user = professional();

    $profile = ProviderProfile::factory()->forUser($user)->create();

    expect($user->providerProfiles)->toHaveCount(1);
    expect($profile->user->is($user))->toBeTrue();
});
