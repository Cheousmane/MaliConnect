<?php

use App\Models\Category;
use App\Models\ProviderProfile;
use App\Models\Subcategory;
use Database\Seeders\CategorySeeder;

/**
 * Tests du catalogue public des catégories.
 */
test('la liste des catégories affiche le catalogue actif', function () {
    Category::factory()->create(['name' => 'Plomberie', 'is_active' => true]);
    Category::factory()->create(['name' => 'Archivée', 'is_active' => false]);

    $response = $this->get('/categories');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('public/Categories')
        ->has('categories', 1),
    );
});

test('le catalogue initial contient cinq catégories hiérarchisées', function () {
    $this->seed(CategorySeeder::class);

    $categories = Category::query()->active()->with('subcategories')->get();

    expect($categories)->toHaveCount(5);
    expect($categories->pluck('name')->all())->toBe([
        'Maison et habitat',
        'Informatique et technologie',
        'Transport et automobile',
        'Services personnels',
        'Professionnels et entreprises',
    ]);

    // Chaque catégorie expose ses sous-catégories actives.
    foreach ($categories as $category) {
        expect($category->subcategories)->not->toBeEmpty();
    }
});

test('le détail d une catégorie liste ses sous-catégories et ses professionnels', function () {
    $category = Category::factory()->create(['name' => 'Plomberie']);
    $subcategory = Subcategory::factory()->create(['category_id' => $category->id]);

    ProviderProfile::factory()->verified()->create([
        'category_id' => $category->id,
        'subcategory_id' => $subcategory->id,
    ]);

    // Un profil non vérifié ne doit pas apparaître.
    ProviderProfile::factory()->pending()->create(['category_id' => $category->id]);

    $response = $this->get("/categories/{$category->slug}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('public/Category')
        ->where('category.name', 'Plomberie')
        ->has('category.subcategories', 1)
        ->where('profiles.total', 1),
    );
});

test('une catégorie inconnue renvoie une erreur 404', function () {
    $this->get('/categories/categorie-inexistante')->assertNotFound();
});

test('une catégorie inactive n est plus accessible', function () {
    $category = Category::factory()->create(['is_active' => false]);

    $this->get("/categories/{$category->slug}")->assertNotFound();
});
