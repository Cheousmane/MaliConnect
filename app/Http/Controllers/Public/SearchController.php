<?php

namespace App\Http\Controllers\Public;

use App\Enums\ProviderProfileSort;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\SearchRequest;
use App\Models\Category;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\Subcategory;
use App\Services\Search\ProviderProfileSearch;
use App\Support\Catalog\ProfilePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Recherche publique des professionnels.
 */
class SearchController extends Controller
{
    /**
     * Affiche les résultats de la recherche.
     */
    public function index(SearchRequest $request, ProviderProfileSearch $search): Response
    {
        $filters = $request->filters();

        $results = $search->search($filters);

        return Inertia::render('public/Search', [
            'results' => [
                'data' => collect($results->items())->map(fn (ProviderProfile $profile) => ProfilePresenter::summary($profile))->all(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'total' => $results->total(),
            ],
            'filters' => [
                'q' => $filters['q'] ?? '',
                'categorie' => $filters['category_id'] ?? '',
                'sous-categorie' => $filters['subcategory_id'] ?? '',
                'commune' => $filters['commune'] ?? '',
                'quartier' => $filters['quartier'] ?? '',
                'prix-min' => $filters['price_min'] ?? '',
                'prix-max' => $filters['price_max'] ?? '',
                'note-min' => $filters['rating_min'] ?? '',
                'disponible' => (bool) $filters['available'],
                'tri' => $filters['sort'],
            ],
            'facets' => [
                'categories' => Category::query()
                    ->active()
                    ->orderBy('sort_order')
                    ->get(['id', 'name', 'slug'])
                    ->all(),
                'subcategories' => Subcategory::query()
                    ->active()
                    ->orderBy('name')
                    ->get(['id', 'name', 'slug', 'category_id'])
                    ->all(),
                'communes' => ProviderProfile::query()
                    ->publiclyVisible()
                    ->whereNotNull('commune')
                    ->distinct()
                    ->orderBy('commune')
                    ->limit(30)
                    ->pluck('commune')
                    ->all(),
                'prices' => $this->priceBounds(),
            ],
            'sorts' => collect(ProviderProfileSort::cases())
                ->map(fn (ProviderProfileSort $sort) => ['value' => $sort->value, 'label' => $sort->label()])
                ->values()
                ->all(),
        ]);
    }

    /**
     * Propose des suggestions pour l'autocomplétion.
     */
    public function suggestions(Request $request, ProviderProfileSearch $search): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        return response()->json([
            'data' => $search->suggestions((string) $validated['q'] ?? ''),
        ]);
    }

    /**
     * Bornes de prix constatées dans le catalogue public.
     *
     * @return array{min: int|null, max: int|null}
     */
    private function priceBounds(): array
    {
        $prices = Service::query()
            ->where('is_active', true)
            ->whereNotNull('price_min')
            ->whereHas('providerProfile', fn ($query) => $query->publiclyVisible())
            ->selectRaw('MIN(price_min) as min_price, MAX(COALESCE(price_max, price_min)) as max_price')
            ->first();

        return [
            'min' => $prices?->min_price !== null ? (int) $prices->min_price : null,
            'max' => $prices?->max_price !== null ? (int) $prices->max_price : null,
        ];
    }
}
