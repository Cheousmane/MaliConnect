<?php

use App\Enums\BusinessMemberRole;
use App\Enums\PricingSheetStatus;
use App\Enums\PricingType;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Category;
use App\Models\Plan;
use App\Models\PricingItem;
use App\Models\PricingSheet;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RoleSeeder;

/**
 * Tests du module de fiches tarifaires professionnelles.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

/**
 * Crée un compte professionnel.
 */
function pricingTestProfessional(): User
{
    $user = User::factory()->create();
    $user->assignRole('professional');

    return $user;
}

/**
 * Crée un profil professionnel pour un compte donné.
 */
function pricingTestProfile(?User $user = null, bool $verified = false): ProviderProfile
{
    $user ??= pricingTestProfessional();

    $factory = ProviderProfile::factory()->forUser($user);

    return ($verified ? $factory->verified() : $factory)->create();
}

/**
 * Crée une fiche tarifaire rattachée à un profil.
 */
function pricingTestSheet(ProviderProfile $profile): PricingSheet
{
    return PricingSheet::factory()->forProfile($profile)->create();
}

test('un professionnel crée une fiche tarifaire avec ses prestations', function () {
    $user = pricingTestProfessional();
    $profile = pricingTestProfile($user);
    $category = Category::factory()->create();

    $response = $this->actingAs($user)->post('/pro/tarifs', [
        'provider_profile_id' => $profile->id,
        'name' => 'Tarifs plomberie',
        'description' => 'Interventions de plomberie à Bamako.',
        'category_id' => $category->id,
        'currency' => 'XOF',
        'general_conditions' => 'Déplacement inclus dans Bamako.',
        'valid_until' => now()->addMonth()->toDateString(),
        'items' => [
            [
                'name' => 'Réparation d’une fuite',
                'description' => 'Remplacement du joint et vérification.',
                'pricing_type' => 'fixed',
                'price' => 25000,
                'billing_unit' => 'par intervention',
                'estimated_duration_minutes' => 60,
                'is_available' => true,
            ],
            [
                'name' => 'Installation sanitaire complète',
                'pricing_type' => 'quote',
                'is_available' => true,
            ],
        ],
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $sheet = PricingSheet::query()->firstOrFail();

    expect($sheet->provider_profile_id)->toBe($profile->id);
    expect($sheet->name)->toBe('Tarifs plomberie');
    expect($sheet->status)->toBe(PricingSheetStatus::Draft);
    expect($sheet->items)->toHaveCount(2);
    expect((float) $sheet->items->first()->price)->toBe(25000.0);
    expect($sheet->items->last()->pricing_type)->toBe(PricingType::Quote);
});

test('un prix est obligatoire pour une prestation à prix fixe', function () {
    $user = pricingTestProfessional();
    $profile = pricingTestProfile($user);

    $response = $this->actingAs($user)->post('/pro/tarifs', [
        'provider_profile_id' => $profile->id,
        'name' => 'Tarifs incomplets',
        'items' => [
            [
                'name' => 'Prestation sans prix',
                'pricing_type' => 'fixed',
                'is_available' => true,
            ],
        ],
    ]);

    $response->assertSessionHasErrors('items.0.price');

    expect(PricingSheet::count())->toBe(0);
});

test('une fourchette de prix doit être cohérente', function () {
    $user = pricingTestProfessional();
    $profile = pricingTestProfile($user);

    $response = $this->actingAs($user)->post('/pro/tarifs', [
        'provider_profile_id' => $profile->id,
        'name' => 'Tarifs incohérents',
        'items' => [
            [
                'name' => 'Prestation',
                'pricing_type' => 'intervention',
                'price_min' => 50000,
                'price_max' => 20000,
                'is_available' => true,
            ],
        ],
    ]);

    $response->assertSessionHasErrors('items.0.price_max');

    expect(PricingSheet::count())->toBe(0);
});

test('un professionnel ne peut pas gérer la fiche d un autre professionnel', function () {
    $owner = pricingTestProfessional();
    $profile = pricingTestProfile($owner);
    $sheet = pricingTestSheet($profile);

    $intruder = pricingTestProfessional();

    $this->actingAs($intruder)->get("/pro/tarifs/{$sheet->id}")->assertForbidden();
    $this->actingAs($intruder)
        ->patch("/pro/tarifs/{$sheet->id}", ['name' => 'Fiche détournée'])
        ->assertForbidden();

    expect($sheet->fresh()->name)->not->toBe('Fiche détournée');
});

test('un client ne peut pas accéder aux fiches tarifaires professionnelles', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $this->actingAs($customer)->get('/pro/tarifs')->assertForbidden();
    $this->actingAs($customer)->get('/pro/tarifs/nouveau')->assertForbidden();
});

test('un gestionnaire d entreprise peut gérer les fiches du profil de l entreprise', function () {
    $owner = User::factory()->create();
    $owner->assignRole('company-manager');

    $business = Business::factory()->forOwner($owner)->create();
    BusinessMember::create([
        'business_id' => $business->id,
        'user_id' => $owner->id,
        'role' => BusinessMemberRole::Owner,
    ]);

    $manager = User::factory()->create();
    $manager->assignRole('company-manager');
    BusinessMember::create([
        'business_id' => $business->id,
        'user_id' => $manager->id,
        'role' => BusinessMemberRole::Manager,
    ]);

    $profile = ProviderProfile::factory()
        ->forUser($owner)
        ->create(['business_id' => $business->id]);
    $sheet = pricingTestSheet($profile);

    $this->actingAs($manager)
        ->patch("/pro/tarifs/{$sheet->id}", [
            'name' => 'Tarifs de l’entreprise',
            'currency' => 'XOF',
        ])
        ->assertSessionHasNoErrors();

    expect($sheet->fresh()->name)->toBe('Tarifs de l’entreprise');
});

test('un collaborateur simple ne peut pas modifier les fiches de l entreprise', function () {
    $owner = User::factory()->create();
    $owner->assignRole('company-manager');

    $business = Business::factory()->forOwner($owner)->create();
    BusinessMember::create([
        'business_id' => $business->id,
        'user_id' => $owner->id,
        'role' => BusinessMemberRole::Owner,
    ]);

    $agent = User::factory()->create();
    $agent->assignRole('professional');
    BusinessMember::create([
        'business_id' => $business->id,
        'user_id' => $agent->id,
        'role' => BusinessMemberRole::Agent,
    ]);

    $profile = ProviderProfile::factory()
        ->forUser($owner)
        ->create(['business_id' => $business->id]);
    $sheet = pricingTestSheet($profile);

    $this->actingAs($agent)
        ->patch("/pro/tarifs/{$sheet->id}", ['name' => 'Fiche modifiée par un agent'])
        ->assertForbidden();

    expect($sheet->fresh()->name)->not->toBe('Fiche modifiée par un agent');
});

test('seules les fiches publiées et non expirées sont accessibles publiquement', function () {
    $profile = pricingTestProfile(null, true);

    $published = PricingSheet::factory()->forProfile($profile)->published()->create();
    $draft = PricingSheet::factory()->forProfile($profile)->create();
    $expired = PricingSheet::factory()->forProfile($profile)->published()->expired()->create();
    $archived = PricingSheet::factory()->forProfile($profile)->archived()->create();

    $this->get("/tarifs/{$published->public_token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/PricingSheet')
            ->where('sheet.id', $published->id),
        );

    $this->get("/tarifs/{$draft->public_token}")->assertNotFound();
    $this->get("/tarifs/{$expired->public_token}")->assertNotFound();
    $this->get("/tarifs/{$archived->public_token}")->assertNotFound();
});

test('une fiche publiée sur un profil non vérifié reste privée', function () {
    $profile = pricingTestProfile(null, false);
    $sheet = PricingSheet::factory()->forProfile($profile)->published()->create();

    $this->get("/tarifs/{$sheet->public_token}")->assertNotFound();
});

test('la fiche publique expose les prix et les prestations sur devis', function () {
    $profile = pricingTestProfile(null, true);
    $sheet = PricingSheet::factory()->forProfile($profile)->published()->create([
        'name' => 'Tarifs dépannage',
    ]);

    PricingItem::factory()->create([
        'pricing_sheet_id' => $sheet->id,
        'name' => 'Dépannage électrique',
        'pricing_type' => PricingType::Intervention,
        'price' => 15000,
        'billing_unit' => 'par intervention',
    ]);

    PricingItem::factory()->quote()->create([
        'pricing_sheet_id' => $sheet->id,
        'name' => 'Mise aux normes',
    ]);

    $this->get("/tarifs/{$sheet->public_token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/PricingSheet')
            ->where('sheet.name', 'Tarifs dépannage')
            ->has('sheet.items', 2)
            ->where('sheet.items.0.formatted_price', '15 000 FCFA / intervention')
            ->where('sheet.items.1.formatted_price', 'Sur devis'),
        );
});

test('le PDF d une fiche publiée est téléchargeable', function () {
    $profile = pricingTestProfile(null, true);
    $sheet = PricingSheet::factory()->forProfile($profile)->published()->create();

    PricingItem::factory()->create([
        'pricing_sheet_id' => $sheet->id,
        'name' => 'Prestation PDF',
        'pricing_type' => PricingType::Fixed,
        'price' => 30000,
    ]);

    $this->get("/tarifs/{$sheet->public_token}/pdf")
        ->assertOk()
        ->assertDownload();
});

test('une fiche peut être dupliquée en brouillon avec ses prestations', function () {
    $this->seed(PlanSeeder::class);

    $user = pricingTestProfessional();
    $profile = pricingTestProfile($user);

    Subscription::factory()
        ->forProfile($profile)
        ->forPlan(Plan::where('code', 'pro')->firstOrFail())
        ->create();

    $sheet = PricingSheet::factory()->forProfile($profile)->published()->create([
        'name' => 'Tarifs plomberie',
    ]);

    PricingItem::factory()->create([
        'pricing_sheet_id' => $sheet->id,
        'name' => 'Réparation',
        'price' => 20000,
    ]);

    $this->actingAs($user)
        ->post("/pro/tarifs/{$sheet->id}/dupliquer")
        ->assertRedirect();

    $copy = PricingSheet::query()->whereKeyNot($sheet->id)->firstOrFail();

    expect($copy->status)->toBe(PricingSheetStatus::Draft);
    expect($copy->public_token)->not->toBe($sheet->public_token);
    expect($copy->name)->toContain('Copie de');
    expect($copy->items)->toHaveCount(1);
    expect($copy->items->first()->name)->toBe('Réparation');
});

test('la duplication est refusée lorsque la limite de fiches est atteinte', function () {
    $user = pricingTestProfessional();
    $profile = pricingTestProfile($user);
    $sheet = PricingSheet::factory()->forProfile($profile)->published()->create();

    $this->actingAs($user)
        ->from('/pro/tarifs')
        ->post("/pro/tarifs/{$sheet->id}/dupliquer")
        ->assertRedirect('/pro/tarifs')
        ->assertSessionHas('error');

    expect(PricingSheet::query()->count())->toBe(1)
        ->and($user->notifications()->count())->toBe(1);
});

test('les prestations peuvent être réorganisées', function () {
    $user = pricingTestProfessional();
    $profile = pricingTestProfile($user);
    $sheet = pricingTestSheet($profile);

    $first = PricingItem::factory()->create([
        'pricing_sheet_id' => $sheet->id,
        'name' => 'Première prestation',
        'sort_order' => 0,
    ]);
    $second = PricingItem::factory()->create([
        'pricing_sheet_id' => $sheet->id,
        'name' => 'Deuxième prestation',
        'sort_order' => 1,
    ]);

    $this->actingAs($user)
        ->post("/pro/tarifs/{$sheet->id}/prestations/reorganiser", [
            'items' => [$second->id, $first->id],
        ])
        ->assertSessionHasNoErrors();

    expect($second->fresh()->sort_order)->toBe(0);
    expect($first->fresh()->sort_order)->toBe(1);
});

test('une fiche ne peut pas être publiée sans prestation disponible', function () {
    $user = pricingTestProfessional();
    $profile = pricingTestProfile($user);
    $sheet = pricingTestSheet($profile);

    $this->actingAs($user)
        ->patch("/pro/tarifs/{$sheet->id}/statut", ['status' => 'published'])
        ->assertSessionHasErrors('status');

    expect($sheet->fresh()->status)->toBe(PricingSheetStatus::Draft);
});

test('une fiche publiée ne peut pas être enregistrée sans prestation disponible', function () {
    $user = pricingTestProfessional();
    $profile = pricingTestProfile($user);
    $sheet = PricingSheet::factory()->forProfile($profile)->published()->create();

    $item = PricingItem::factory()->create([
        'pricing_sheet_id' => $sheet->id,
        'name' => 'Prestation disponible',
        'is_available' => true,
    ]);

    $this->actingAs($user)
        ->patch("/pro/tarifs/{$sheet->id}", [
            'name' => $sheet->name,
            'currency' => 'XOF',
            'items' => [
                [
                    'id' => $item->id,
                    'name' => $item->name,
                    'pricing_type' => 'fixed',
                    'price' => 25000,
                    'is_available' => false,
                ],
            ],
        ])
        ->assertSessionHasErrors('items');

    expect($item->fresh()->is_available)->toBeTrue();
});

test('la dernière prestation disponible d une fiche publiée ne peut pas être supprimée', function () {
    $user = pricingTestProfessional();
    $profile = pricingTestProfile($user);
    $sheet = PricingSheet::factory()->forProfile($profile)->published()->create();

    $available = PricingItem::factory()->create([
        'pricing_sheet_id' => $sheet->id,
        'name' => 'Dernière prestation disponible',
        'is_available' => true,
    ]);

    PricingItem::factory()->unavailable()->create([
        'pricing_sheet_id' => $sheet->id,
        'name' => 'Prestation indisponible',
    ]);

    $this->actingAs($user)
        ->delete("/pro/tarifs/{$sheet->id}/prestations/{$available->id}")
        ->assertSessionHas('error');

    expect($available->fresh())->not->toBeNull();
});

test('le professionnel peut prévisualiser sa fiche avant publication', function () {
    $user = pricingTestProfessional();
    $profile = pricingTestProfile($user);
    $sheet = pricingTestSheet($profile);

    $this->actingAs($user)
        ->get("/pro/tarifs/{$sheet->id}/apercu")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/PricingSheet')
            ->where('preview', true)
            ->where('sheet.id', $sheet->id),
        );
});

test('le profil public expose les fiches publiées dans l onglet tarifs', function () {
    $profile = pricingTestProfile(null, true);
    $sheet = PricingSheet::factory()->forProfile($profile)->published()->create();

    PricingItem::factory()->create(['pricing_sheet_id' => $sheet->id]);

    $this->get("/professionnels/{$profile->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/Professional')
            ->has('pricingSheets', 1)
            ->where('pricingSheets.0.id', $sheet->id),
        );
});

test('une demande de devis peut être rattachée à une prestation tarifée', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $profile = pricingTestProfile(null, true);
    $sheet = PricingSheet::factory()->forProfile($profile)->published()->create();
    $item = PricingItem::factory()->create([
        'pricing_sheet_id' => $sheet->id,
        'name' => 'Réparation d’une fuite',
        'pricing_type' => PricingType::Fixed,
        'price' => 25000,
    ]);

    $this->actingAs($customer)
        ->post('/demandes', [
            'provider_profile_id' => $profile->id,
            'pricing_item_id' => $item->id,
            'title' => 'Devis pour une fuite',
            'description' => 'Une fuite importante sous l’évier de la cuisine doit être réparée rapidement.',
            'city' => 'Bamako',
        ])
        ->assertSessionHasNoErrors();

    $serviceRequest = ServiceRequest::query()->firstOrFail();

    expect($serviceRequest->pricing_item_id)->toBe($item->id);
});

test('une prestation d une fiche non publiée ne peut pas être rattachée à une demande', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $profile = pricingTestProfile(null, true);
    $sheet = PricingSheet::factory()->forProfile($profile)->create();
    $item = PricingItem::factory()->create(['pricing_sheet_id' => $sheet->id]);

    $this->actingAs($customer)
        ->post('/demandes', [
            'provider_profile_id' => $profile->id,
            'pricing_item_id' => $item->id,
            'title' => 'Devis pour une fuite',
            'description' => 'Une fuite importante sous l’évier de la cuisine doit être réparée rapidement.',
            'city' => 'Bamako',
        ])
        ->assertNotFound();

    expect(ServiceRequest::count())->toBe(0);
});

test('les prix absents ne sont jamais remplacés par un montant fictif', function () {
    $profile = pricingTestProfile();
    $sheet = pricingTestSheet($profile);

    $quote = PricingItem::factory()->quote()->create(['pricing_sheet_id' => $sheet->id]);
    $diagnostic = PricingItem::factory()->diagnostic()->create(['pricing_sheet_id' => $sheet->id]);
    $missing = PricingItem::factory()->create([
        'pricing_sheet_id' => $sheet->id,
        'pricing_type' => PricingType::Fixed,
        'price' => null,
        'price_min' => null,
        'price_max' => null,
    ]);

    expect($quote->formatted_price)->toBe('Sur devis');
    expect($diagnostic->formatted_price)->toBe('Sur diagnostic');
    expect($missing->formatted_price)->toBe('Prix non communiqué');
});

test('une prestation tarifée peut être liée à un service existant', function () {
    $user = pricingTestProfessional();
    $profile = pricingTestProfile($user);
    $service = Service::factory()->create([
        'provider_profile_id' => $profile->id,
        'name' => 'Réparation de fuite',
    ]);

    $this->actingAs($user)->post('/pro/tarifs', [
        'provider_profile_id' => $profile->id,
        'name' => 'Tarifs liés au catalogue',
        'items' => [
            [
                'name' => 'Réparation de fuite',
                'service_id' => $service->id,
                'pricing_type' => 'fixed',
                'price' => 18000,
                'is_available' => true,
            ],
        ],
    ])->assertSessionHasNoErrors();

    $item = PricingItem::query()->firstOrFail();

    expect($item->service_id)->toBe($service->id);
});
