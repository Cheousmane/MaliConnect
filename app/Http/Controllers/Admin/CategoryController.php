<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\Admin\AuditLogger;
use App\Support\Identity\SlugGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestion administrative du catalogue : catégories.
 */
class CategoryController extends Controller
{
    /**
     * Affiche les catégories et leurs volumes rattachés.
     */
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('categories.manage'), 403);

        $search = trim((string) $request->string('search'));
        $active = (string) $request->string('active');

        $paginator = Category::query()
            ->withCount(['subcategories', 'providerProfiles'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->when($active === '1', fn ($query) => $query->where('is_active', true))
            ->when($active === '0', fn ($query) => $query->where('is_active', false))
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/Categories', [
            'categories' => [
                'data' => collect($paginator->items())->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'icon' => $category->icon,
                    'color' => $category->color,
                    'is_active' => $category->is_active,
                    'sort_order' => $category->sort_order,
                    'subcategories_count' => $category->subcategories_count,
                    'profiles_count' => $category->provider_profiles_count,
                ])->all(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'search' => $search,
                'active' => $active,
            ],
        ]);
    }

    /**
     * Crée une catégorie.
     */
    public function store(StoreCategoryRequest $request, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->can('categories.manage'), 403);

        $category = Category::create([
            'name' => $request->validated('name'),
            'slug' => SlugGenerator::unique('categories', $request->validated('name')),
            'description' => $request->validated('description'),
            'icon' => $request->validated('icon'),
            'color' => $request->validated('color'),
            'is_active' => $request->validated('is_active', true),
            'sort_order' => $request->validated('sort_order', 0),
        ]);

        $audit->log(
            'category.created',
            sprintf('Catégorie « %s » créée.', $category->name),
            $category,
            ['name' => $category->name],
            $request->user(),
            $request,
        );

        return back()->with('success', 'La catégorie a été créée.');
    }

    /**
     * Modifie une catégorie.
     */
    public function update(UpdateCategoryRequest $request, Category $category, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->can('categories.manage'), 403);

        $name = $request->validated('name');

        $category->update([
            'name' => $name,
            'slug' => SlugGenerator::unique('categories', $name, $category->id),
            'description' => $request->validated('description'),
            'icon' => $request->validated('icon'),
            'color' => $request->validated('color'),
            'is_active' => $request->validated('is_active', $category->is_active),
            'sort_order' => $request->validated('sort_order', $category->sort_order),
        ]);

        $audit->log(
            'category.updated',
            sprintf('Catégorie « %s » modifiée.', $category->name),
            $category,
            ['name' => $category->name, 'is_active' => $category->is_active],
            $request->user(),
            $request,
        );

        return back()->with('success', 'La catégorie a été mise à jour.');
    }

    /**
     * Active ou désactive une catégorie.
     */
    public function updateActivation(Request $request, Category $category, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->can('categories.manage'), 403);

        $validated = $request->validate(['is_active' => ['required', 'boolean']]);

        $category->update(['is_active' => (bool) $validated['is_active']]);

        $audit->log(
            'category.activation.updated',
            sprintf(
                'Catégorie « %s » %s.',
                $category->name,
                $category->is_active ? 'activée' : 'désactivée',
            ),
            $category,
            ['is_active' => $category->is_active],
            $request->user(),
            $request,
        );

        return back()->with('success', 'La visibilité de la catégorie a été mise à jour.');
    }
}
