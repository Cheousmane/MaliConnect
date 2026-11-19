<?php

use App\Enums\AccountStatus;
use App\Models\Category;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\Subcategory;
use Illuminate\Support\Facades\DB;

/**
 * Tests du moteur de recherche publique.
 */
test('la recherche filtre par mot-clé', function () {
    ProviderProfile::factory()->verified()->create(['business_name' => 'Atelier Koné']);
    ProviderProfile::factory()->verified()->create(['business_name' => 'Studio Photo']);

    $response = $this->get('/recherche?q=koné');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('public/Search')
        ->where('results.total', 1)
        ->where('results.data.0.business_name', 'Atelier Koné'),
    );
});

test('la recherche filtre par catégorie et sous-catégorie', function () {
    $category = Category::factory()->create();
    $other = Category::factory()->create();
    $subcategory = Subcategory::factory()->create(['category_id' => $category->id]);

    ProviderProfile::factory()->verified()->create([
        'category_id' => $category->id,
        'subcategory_id' => $subcategory->id,
    ]);
    ProviderProfile::factory()->verified()->create(['category_id' => $other->id]);

    $this->get("/recherche?categorie={$category->id}")
        ->assertInertia(fn ($page) => $page->where('results.total', 1));

    $this->get("/recherche?sous-categorie={$subcategory->id}")
        ->assertInertia(fn ($page) => $page->where('results.total', 1));
});

test('la recherche filtre par commune et quartier', function () {
    ProviderProfile::factory()->verified()->create(['commune' => 'Commune IV', 'quartier' => 'Badalabougou']);
    ProviderProfile::factory()->verified()->create(['commune' => 'Commune V', 'quartier' => 'Koulouba']);

    $this->get('/recherche?commune=Commune IV')
        ->assertInertia(fn ($page) => $page->where('results.total', 1));

    $this->get('/recherche?quartier=Koulouba')
        ->assertInertia(fn ($page) => $page->where('results.total', 1));
});

test('la recherche filtre par fourchette de prix', function () {
    $profile = ProviderProfile::factory()->verified()->create();
    Service::factory()->create(['provider_profile_id' => $profile->id, 'price_min' => 5000, 'price_max' => 15000]);
    ProviderProfile::factory()->verified()->create();

    $this->get('/recherche?prix-min=10000&prix-max=20000')
        ->assertInertia(fn ($page) => $page->where('results.total', 1));

    $this->get('/recherche?prix-min=100000')
        ->assertInertia(fn ($page) => $page->where('results.total', 0));
});

test('la recherche filtre par note minimale', function () {
    ProviderProfile::factory()->verified()->create(['rating_average' => 4.8, 'rating_count' => 12]);
    ProviderProfile::factory()->verified()->create(['rating_average' => 3.2, 'rating_count' => 4]);

    $this->get('/recherche?note-min=4')
        ->assertInertia(fn ($page) => $page->where('results.total', 1));
});

test('la recherche filtre par disponibilité', function () {
    ProviderProfile::factory()->verified()->create(['is_available' => true]);
    ProviderProfile::factory()->verified()->create(['is_available' => false]);

    $this->get('/recherche?disponible=1')
        ->assertInertia(fn ($page) => $page->where('results.total', 1));
});

test('la recherche ne présente jamais un profil non vérifié', function () {
    ProviderProfile::factory()->pending()->create();
    ProviderProfile::factory()->refused()->create();
    ProviderProfile::factory()->suspended()->create();

    $this->get('/recherche')->assertInertia(fn ($page) => $page->where('results.total', 0));
});

test('la recherche exclut le profil d un compte suspendu', function () {
    $profile = ProviderProfile::factory()->verified()->create();

    $profile->user->forceFill(['status' => AccountStatus::Suspended])->save();

    $this->get('/recherche')->assertInertia(fn ($page) => $page->where('results.total', 0));
});

test('la recherche trie par note', function () {
    ProviderProfile::factory()->verified()->create(['business_name' => 'B', 'rating_average' => 3.0]);
    ProviderProfile::factory()->verified()->create(['business_name' => 'A', 'rating_average' => 4.9]);

    $this->get('/recherche?tri=note')
        ->assertInertia(fn ($page) => $page->where('results.data.0.business_name', 'A'));
});

test('la recherche trie par pertinence le mot-clé en tête', function () {
    ProviderProfile::factory()->verified()->create(['business_name' => 'Plombier Koné', 'rating_average' => 3.0]);
    ProviderProfile::factory()->verified()->create(['business_name' => 'Koné Peinture', 'rating_average' => 4.9]);

    // Le nom qui commence par le mot-clé passe devant, la note départage ensuite.
    $this->get('/recherche?q=koné&tri=pertinence')
        ->assertInertia(fn ($page) => $page->where('results.data.0.business_name', 'Koné Peinture'));
});

test('la recherche pagine les résultats', function () {
    ProviderProfile::factory()->count(15)->verified()->create();

    $this->get('/recherche')->assertInertia(fn ($page) => $page
        ->where('results.total', 15)
        ->where('results.last_page', 2)
        ->has('results.data', 12),
    );

    $this->get('/recherche?page=2')->assertInertia(fn ($page) => $page
        ->where('results.current_page', 2)
        ->has('results.data', 3),
    );
});

test('la recherche renvoie un état sans résultat', function () {
    $response = $this->get('/recherche?q=introuvable');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('public/Search')
        ->where('results.total', 0)
        ->has('results.data', 0),
    );
});

test('la recherche ne provoque pas de problème N+1', function () {
    ProviderProfile::factory()->count(12)->verified()->create();

    $queries = 0;

    DB::listen(function () use (&$queries) {
        $queries++;
    });

    $this->get('/recherche')->assertOk();

    // Un nombre de requêtes stable, indépendant du volume de résultats.
    expect($queries)->toBeLessThan(25);
});

test('les suggestions d autocomplétion sont proposées', function () {
    ProviderProfile::factory()->verified()->create(['business_name' => 'Atelier Koné']);
    Category::factory()->create(['name' => 'Plomberie']);

    $response = $this->get('/recherche/suggestions?q=kon');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.type', 'professionnel');
});
