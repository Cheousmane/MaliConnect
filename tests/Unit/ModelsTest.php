<?php

use App\Models\Category;
use App\Models\Professional;
use App\Models\Subcategory;
use App\Models\User;

// ─── Category ─────────────────────────────────────────────────────────────────

test('une catégorie peut être créée avec ses champs obligatoires', function () {
    $category = Category::create([
        'name' => 'Plomberie',
        'slug' => 'plomberie',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    expect($category->id)->toBeInt()
        ->and($category->name)->toBe('Plomberie')
        ->and($category->is_active)->toBeTrue();
});

test('une catégorie a des sous-catégories', function () {
    $category = Category::factory()->create();
    $sub = Subcategory::factory()->create(['category_id' => $category->id]);

    expect($category->subcategories)->toHaveCount(1)
        ->and($category->subcategories->first()->id)->toBe($sub->id);
});

test('le scope active filtre correctement les catégories', function () {
    Category::factory()->create(['is_active' => true]);
    Category::factory()->create(['is_active' => false]);

    $actives = Category::active()->get();

    expect($actives)->toHaveCount(1)
        ->and($actives->first()->is_active)->toBeTrue();
});

// ─── Subcategory ──────────────────────────────────────────────────────────────

test('une sous-catégorie appartient à une catégorie', function () {
    $category = Category::factory()->create();
    $sub = Subcategory::factory()->create(['category_id' => $category->id]);

    expect($sub->category->id)->toBe($category->id);
});

// ─── Professional ─────────────────────────────────────────────────────────────

test('un professionnel est lié à un utilisateur et une catégorie', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();

    $professional = Professional::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'business_name' => 'Plomberie Diallo',
        'slug' => 'plomberie-diallo',
        'status' => Professional::STATUS_PENDING,
        'subscription_plan' => Professional::PLAN_FREE,
    ]);

    expect($professional->user->id)->toBe($user->id)
        ->and($professional->category->id)->toBe($category->id);
});

test('le scope active filtre les professionnels actifs et vérifiés', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $category = Category::factory()->create();

    Professional::create([
        'user_id' => $user1->id,
        'category_id' => $category->id,
        'business_name' => 'Pro Actif',
        'slug' => 'pro-actif',
        'status' => Professional::STATUS_ACTIVE,
        'is_active' => true,
        'subscription_plan' => Professional::PLAN_FREE,
    ]);

    Professional::create([
        'user_id' => $user2->id,
        'category_id' => $category->id,
        'business_name' => 'Pro Suspendu',
        'slug' => 'pro-suspendu',
        'status' => Professional::STATUS_SUSPENDED,
        'is_active' => true,
        'subscription_plan' => Professional::PLAN_FREE,
    ]);

    expect(Professional::active()->count())->toBe(1);
});

test("l'attribut has_active_subscription fonctionne correctement", function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();

    $freeProf = Professional::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'business_name' => 'Gratuit',
        'slug' => 'prof-gratuit',
        'subscription_plan' => Professional::PLAN_FREE,
        'subscription_expires_at' => null,
    ]);

    expect($freeProf->has_active_subscription)->toBeFalse();
});
