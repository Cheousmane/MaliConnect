<?php

namespace App\Services\Search;

use App\Enums\ProviderProfileSort;
use App\Models\Category;
use App\Models\ProviderProfile;
use App\Models\Subcategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Moteur de recherche des profils professionnels.
 *
 * La requête reste une seule requête paginée : les relations affichées sont
 * chargées en eager loading et les compteurs utilisent des sous-requêtes, ce
 * qui évite tout problème N+1.
 *
 * La structure des filtres (mot-clé, catégorie, sous-catégorie, commune,
 * quartier, prix, note, vérification, disponibilité) est prête à accueillir
 * une recherche géographique avancée (distance, rayon, bornes GPS).
 */
class ProviderProfileSearch
{
    /**
     * Nombre de profils renvoyés par page.
     */
    public const PER_PAGE = 12;

    /**
     * Exécute la recherche et pagine les résultats.
     *
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters = []): LengthAwarePaginator
    {
        $query = $this->query($filters);

        $this->sort($query, $filters);

        return $query
            ->paginate((int) ($filters['per_page'] ?? self::PER_PAGE))
            ->withQueryString();
    }

    /**
     * Construit la requête de base et applique les filtres.
     *
     * @param  array<string, mixed>  $filters
     */
    private function query(array $filters): Builder
    {
        $query = ProviderProfile::query()
            ->with(['category', 'subcategory', 'business'])
            ->withCount(['services', 'media']);

        // Seuls les profils réellement publics sont recherchables : profil
        // vérifié et actif, compte actif et entreprise vérifiée si applicable.
        $query->publiclyVisible();

        if (! empty($filters['q'])) {
            $this->applyKeyword($query, (string) $filters['q']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['subcategory_id'])) {
            $query->where('subcategory_id', (int) $filters['subcategory_id']);
        }

        if (! empty($filters['commune'])) {
            $query->where('commune', (string) $filters['commune']);
        }

        if (! empty($filters['quartier'])) {
            $query->where('quartier', 'like', '%'.(string) $filters['quartier'].'%');
        }

        if (! empty($filters['rating_min'])) {
            $query->where('rating_average', '>=', (float) $filters['rating_min']);
        }

        if (! empty($filters['available'])) {
            $query->where('is_available', true);
        }

        $this->applyPrice($query, $filters);

        return $query;
    }

    /**
     * Filtre le mot-clé sur le nom commercial, la description et les services.
     */
    private function applyKeyword(Builder $query, string $term): void
    {
        $like = '%'.mb_strtolower($term).'%';

        $query->where(function (Builder $sub) use ($like) {
            $sub->whereRaw('LOWER(business_name) LIKE ?', [$like])
                ->orWhereRaw('LOWER(description) LIKE ?', [$like])
                ->orWhereHas('services', fn (Builder $service) => $service->whereRaw('LOWER(name) LIKE ?', [$like]));
        });
    }

    /**
     * Filtre la fourchette de prix sur les services actifs du profil.
     *
     * @param  array<string, mixed>  $filters
     */
    private function applyPrice(Builder $query, array $filters): void
    {
        $min = $filters['price_min'] ?? null;
        $max = $filters['price_max'] ?? null;

        if ($min === null && $max === null) {
            return;
        }

        $query->whereHas('services', function (Builder $service) use ($min, $max) {
            $service->where('is_active', true)
                ->whereNotNull('price_min');

            if ($max !== null) {
                $service->where('price_min', '<=', (float) $max);
            }

            if ($min !== null) {
                $service->where(function (Builder $sub) use ($min) {
                    $sub->whereNull('price_max')->orWhere('price_max', '>=', (float) $min);
                });
            }
        });
    }

    /**
     * Applique le tri demandé.
     *
     * @param  array<string, mixed>  $filters
     */
    private function sort(Builder $query, array $filters): void
    {
        $sort = ProviderProfileSort::tryFrom((string) ($filters['sort'] ?? ProviderProfileSort::Relevance->value))
            ?? ProviderProfileSort::Relevance;

        if ($sort === ProviderProfileSort::Rating) {
            $query->orderByDesc('rating_average')->orderByDesc('rating_count');

            return;
        }

        if ($sort === ProviderProfileSort::Recent) {
            $query->orderByDesc('created_at');

            return;
        }

        // Tri par pertinence : le mot-clé.search exacte d'abord, puis la note.
        if (! empty($filters['q'])) {
            $like = mb_strtolower((string) $filters['q']);

            $query->orderByRaw(
                'CASE WHEN LOWER(business_name) LIKE ? THEN 0 WHEN LOWER(business_name) LIKE ? THEN 1 ELSE 2 END',
                [$like.'%', '%'.$like.'%'],
            );
        }

        $query->orderByDesc('rating_average')->orderByDesc('rating_count')->orderByDesc('created_at');
    }

    /**
     * Propose des suggestions pour l'autocomplétion.
     *
     * @return list<array{type: string, label: string, url: string}>
     */
    public function suggestions(string $term, int $limit = 6): array
    {
        $term = trim($term);

        if (mb_strlen($term) < 2) {
            return [];
        }

        $like = '%'.mb_strtolower($term).'%';

        $suggestions = [];

        foreach (ProviderProfile::query()
            ->publiclyVisible()
            ->whereRaw('LOWER(business_name) LIKE ?', [$like])
            ->orderByDesc('rating_average')
            ->limit($limit)
            ->get(['business_name', 'slug']) as $profile) {
            $suggestions[] = [
                'type' => 'professionnel',
                'label' => $profile->business_name,
                'url' => route('professionals.show', $profile->slug),
            ];
        }

        foreach (Category::query()
            ->active()
            ->whereRaw('LOWER(name) LIKE ?', [$like])
            ->limit(3)
            ->get(['name', 'slug']) as $category) {
            $suggestions[] = [
                'type' => 'catégorie',
                'label' => $category->name,
                'url' => route('categories.show', $category->slug),
            ];
        }

        foreach (Subcategory::query()
            ->with('category')
            ->active()
            ->whereRaw('LOWER(name) LIKE ?', [$like])
            ->limit(3)
            ->get(['name', 'slug', 'category_id']) as $subcategory) {
            $suggestions[] = [
                'type' => 'sous-catégorie',
                'label' => $subcategory->name,
                'url' => route('categories.show', $subcategory->category->slug).'?sous-categorie='.$subcategory->slug,
            ];
        }

        return $suggestions;
    }
}
