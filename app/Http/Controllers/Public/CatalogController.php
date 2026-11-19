<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ProviderProfile;
use App\Services\Search\ProviderProfileSearch;
use App\Support\Catalog\ProfilePresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Catalogue public des catégories et sous-catégories.
 */
class CatalogController extends Controller
{
    /**
     * Affiche la liste des catégories actives.
     */
    public function index(): Response
    {
        $categories = Category::query()
            ->active()
            ->with(['subcategories' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->withCount(['providerProfiles' => fn ($query) => $query->publiclyVisible()])
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('public/Categories', [
            'categories' => $categories->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'icon' => $category->icon,
                'color' => $category->color,
                'profiles_count' => $category->provider_profiles_count,
                'subcategories' => $category->subcategories->map(fn ($subcategory) => [
                    'id' => $subcategory->id,
                    'name' => $subcategory->name,
                    'slug' => $subcategory->slug,
                ])->values()->all(),
            ])->values()->all(),
        ]);
    }

    /**
     * Affiche le détail d'une catégorie et ses professionnels vérifiés.
     */
    public function show(Request $request, Category $category, ProviderProfileSearch $search): Response
    {
        abort_unless($category->is_active, 404);

        $subcategorySlug = (string) $request->string('sous-categorie');

        $filters = array_filter([
            'category_id' => $category->id,
            'subcategory_id' => $category->subcategories()->where('slug', $subcategorySlug)->value('id'),
            'q' => $request->string('q')->toString() ?: null,
            'sort' => 'rating',
        ], fn ($value) => $value !== null && $value !== '');

        $results = $search->search($filters);

        return Inertia::render('public/Category', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'icon' => $category->icon,
                'color' => $category->color,
                'subcategories' => $category->subcategories()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn ($subcategory) => [
                        'id' => $subcategory->id,
                        'name' => $subcategory->name,
                        'slug' => $subcategory->slug,
                    ])
                    ->values()
                    ->all(),
            ],
            'selectedSubcategory' => $subcategorySlug,
            'profiles' => [
                'data' => collect($results->items())->map(fn (ProviderProfile $profile) => $this->summary($profile))->all(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'total' => $results->total(),
            ],
        ]);
    }

    /**
     * Résume un profil pour les listes publiques.
     *
     * @return array<string, mixed>
     */
    private function summary(ProviderProfile $profile): array
    {
        return ProfilePresenter::summary($profile);
    }
}
