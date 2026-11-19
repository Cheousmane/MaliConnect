<?php

use App\Enums\BusinessMemberRole;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Category;
use App\Models\Plan;
use App\Models\ProviderProfile;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RoleSeeder;

/**
 * Tests du module de gestion des entreprises et de leurs collaborateurs.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

/**
 * Crée un compte gestionnaire d'entreprise.
 */
function manager(): User
{
    $user = User::factory()->create();
    $user->assignRole('company-manager');

    return $user;
}

test('un gestionnaire peut créer une entreprise', function () {
    $user = manager();

    $response = $this->actingAs($user)->post('/pro/businesses', [
        'name' => 'Koné & Fils',
        'legal_name' => 'Koné & Fils SARL',
        'city' => 'Bamako',
        'commune' => 'Commune IV',
    ]);

    $response->assertSessionHasNoErrors();

    $business = Business::where('owner_id', $user->id)->firstOrFail();

    expect($business->name)->toBe('Koné & Fils');
    expect($business->slug)->toBe('kone-fils');

    // Le créateur devient responsable de l'entreprise.
    expect($business->members()->count())->toBe(1);
    expect($business->members()->first()->role)->toBe(BusinessMemberRole::Owner);
});

test('un client ne peut pas créer d entreprise', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $this->actingAs($user)
        ->post('/pro/businesses', ['name' => 'Koné & Fils', 'city' => 'Bamako'])
        ->assertForbidden();

    expect(Business::count())->toBe(0);
});

test('un responsable peut ajouter un collaborateur', function () {
    $this->seed(PlanSeeder::class);

    $owner = manager();
    $collaborator = User::factory()->create();

    $business = Business::factory()->forOwner($owner)->create();
    BusinessMember::create([
        'business_id' => $business->id,
        'user_id' => $owner->id,
        'role' => BusinessMemberRole::Owner,
    ]);

    // La gestion des collaborateurs est réservée aux profils BUSINESS :
    // le profil de l'entreprise doit donc porter un abonnement actif.
    $profile = ProviderProfile::factory()->forUser($owner)->create([
        'business_id' => $business->id,
    ]);
    $plan = Plan::query()->where('code', 'business')->firstOrFail();

    Subscription::factory()->create([
        'business_id' => $business->id,
        'plan_id' => $plan->id,
        'plan' => $plan->code,
        'status' => SubscriptionStatus::Active,
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
        'amount' => $plan->price_monthly,
        'currency' => $plan->currency,
    ]);

    $this->actingAs($owner)
        ->post("/pro/businesses/{$business->id}/members", [
            'user_id' => $collaborator->id,
            'role' => 'agent',
        ])
        ->assertSessionHasNoErrors();

    expect($business->members()->count())->toBe(2);
    expect($business->members()->where('user_id', $collaborator->id)->first()->role)->toBe(BusinessMemberRole::Agent);
});

test('un compte ne peut pas être ajouté deux fois dans la même entreprise', function () {
    $owner = manager();
    $collaborator = User::factory()->create();

    $business = Business::factory()->forOwner($owner)->create();
    BusinessMember::create([
        'business_id' => $business->id,
        'user_id' => $owner->id,
        'role' => BusinessMemberRole::Owner,
    ]);
    BusinessMember::create([
        'business_id' => $business->id,
        'user_id' => $collaborator->id,
        'role' => BusinessMemberRole::Agent,
    ]);

    $this->actingAs($owner)
        ->post("/pro/businesses/{$business->id}/members", [
            'user_id' => $collaborator->id,
            'role' => 'agent',
        ])
        ->assertSessionHasErrors('user_id');

    expect($business->members()->count())->toBe(2);
});

test('un collaborateur simple ne peut pas gérer les membres', function () {
    $owner = manager();
    $agent = User::factory()->create();

    $business = Business::factory()->forOwner($owner)->create();
    BusinessMember::create([
        'business_id' => $business->id,
        'user_id' => $owner->id,
        'role' => BusinessMemberRole::Owner,
    ]);
    $member = BusinessMember::create([
        'business_id' => $business->id,
        'user_id' => $agent->id,
        'role' => BusinessMemberRole::Agent,
    ]);

    $this->actingAs($agent)
        ->post("/pro/businesses/{$business->id}/members", [
            'user_id' => User::factory()->create()->id,
            'role' => 'agent',
        ])
        ->assertForbidden();

    expect($business->members()->count())->toBe(2);
});

test('le rôle d un collaborateur peut être modifié par le responsable', function () {
    $owner = manager();
    $agent = User::factory()->create();

    $business = Business::factory()->forOwner($owner)->create();
    BusinessMember::create([
        'business_id' => $business->id,
        'user_id' => $owner->id,
        'role' => BusinessMemberRole::Owner,
    ]);
    $member = BusinessMember::create([
        'business_id' => $business->id,
        'user_id' => $agent->id,
        'role' => BusinessMemberRole::Agent,
    ]);

    $this->actingAs($owner)
        ->patch("/pro/businesses/{$business->id}/members/{$member->id}", ['role' => 'manager'])
        ->assertSessionHasNoErrors();

    expect($member->fresh()->role)->toBe(BusinessMemberRole::Manager);
});

test('le dernier responsable ne peut pas être retiré', function () {
    $owner = manager();

    $business = Business::factory()->forOwner($owner)->create();
    $member = BusinessMember::create([
        'business_id' => $business->id,
        'user_id' => $owner->id,
        'role' => BusinessMemberRole::Owner,
    ]);

    $this->actingAs($owner)
        ->delete("/pro/businesses/{$business->id}/members/{$member->id}")
        ->assertSessionHas('error');

    expect($business->members()->count())->toBe(1);
});

test('un responsable ne peut pas modifier un collaborateur d une autre entreprise', function () {
    $ownerA = manager();
    $ownerB = manager();

    $businessA = Business::factory()->forOwner($ownerA)->create();
    $businessB = Business::factory()->forOwner($ownerB)->create();

    BusinessMember::create([
        'business_id' => $businessA->id,
        'user_id' => $ownerA->id,
        'role' => BusinessMemberRole::Owner,
    ]);
    BusinessMember::create([
        'business_id' => $businessB->id,
        'user_id' => $ownerB->id,
        'role' => BusinessMemberRole::Owner,
    ]);

    $foreignMember = BusinessMember::create([
        'business_id' => $businessB->id,
        'user_id' => User::factory()->create()->id,
        'role' => BusinessMemberRole::Agent,
    ]);

    $this->actingAs($ownerA)
        ->patch("/pro/businesses/{$businessA->id}/members/{$foreignMember->id}", ['role' => 'manager'])
        ->assertNotFound();

    expect($foreignMember->fresh()->role)->toBe(BusinessMemberRole::Agent);
});

test('un responsable ne peut pas supprimer un collaborateur d une autre entreprise', function () {
    $ownerA = manager();
    $ownerB = manager();

    $businessA = Business::factory()->forOwner($ownerA)->create();
    $businessB = Business::factory()->forOwner($ownerB)->create();

    BusinessMember::create([
        'business_id' => $businessA->id,
        'user_id' => $ownerA->id,
        'role' => BusinessMemberRole::Owner,
    ]);
    BusinessMember::create([
        'business_id' => $businessB->id,
        'user_id' => $ownerB->id,
        'role' => BusinessMemberRole::Owner,
    ]);

    $foreignMember = BusinessMember::create([
        'business_id' => $businessB->id,
        'user_id' => User::factory()->create()->id,
        'role' => BusinessMemberRole::Agent,
    ]);

    $this->actingAs($ownerA)
        ->delete("/pro/businesses/{$businessA->id}/members/{$foreignMember->id}")
        ->assertNotFound();

    expect($foreignMember->fresh())->not->toBeNull();
});

test('un tiers ne peut pas consulter une entreprise', function () {
    $owner = manager();
    $stranger = User::factory()->create();

    $business = Business::factory()->forOwner($owner)->create();

    $this->actingAs($stranger)->get("/pro/businesses/{$business->id}")->assertForbidden();
});

test('une entreprise peut porter plusieurs profils professionnels', function () {
    $owner = manager();

    $business = Business::factory()->forOwner($owner)->create();
    BusinessMember::create([
        'business_id' => $business->id,
        'user_id' => $owner->id,
        'role' => BusinessMemberRole::Owner,
    ]);

    ProviderProfile::factory()->count(2)->create(['business_id' => $business->id, 'user_id' => $owner->id]);

    expect($business->profiles()->count())->toBe(2);
});

test('un gestionnaire peut gérer les profils de son entreprise', function () {
    $owner = manager();
    $collaborator = User::factory()->create();

    $business = Business::factory()->forOwner($owner)->create();
    BusinessMember::create([
        'business_id' => $business->id,
        'user_id' => $owner->id,
        'role' => BusinessMemberRole::Owner,
    ]);

    $profile = ProviderProfile::factory()->create([
        'business_id' => $business->id,
        'user_id' => $collaborator->id,
    ]);

    $this->actingAs($owner)
        ->patch("/pro/profiles/{$profile->id}", [
            'business_name' => 'Profil de l entreprise',
            'category_id' => $profile->category_id,
            'city' => 'Bamako',
        ])
        ->assertSessionHasNoErrors();

    expect($profile->refresh()->business_name)->toBe('Profil de l entreprise');
});

test('un profil créé pour une entreprise est rattaché à celle-ci', function () {
    $owner = manager();

    $business = Business::factory()->forOwner($owner)->create();
    BusinessMember::create([
        'business_id' => $business->id,
        'user_id' => $owner->id,
        'role' => BusinessMemberRole::Owner,
    ]);

    $this->actingAs($owner)
        ->post('/pro/profiles', [
            'business_id' => $business->id,
            'business_name' => 'Atelier Koné',
            'category_id' => Category::factory()->create()->id,
            'city' => 'Bamako',
        ])
        ->assertSessionHasNoErrors();

    expect($business->profiles()->count())->toBe(1);
});

test('les relations entre utilisateur, entreprise et profil sont cohérentes', function () {
    $owner = manager();

    $business = Business::factory()->forOwner($owner)->create();
    $member = BusinessMember::create([
        'business_id' => $business->id,
        'user_id' => $owner->id,
        'role' => BusinessMemberRole::Owner,
    ]);

    expect($owner->businesses)->toHaveCount(1);
    expect($owner->businessMemberships)->toHaveCount(1);
    expect($member->business->is($business))->toBeTrue();
    expect($business->owner->is($owner))->toBeTrue();
});
