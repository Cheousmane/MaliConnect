<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubcategoryRequest;
use App\Http\Requests\Admin\UpdateSubcategoryRequest;
use App\Models\Category;
use App\Models\Subcategory;
use App\Services\Admin\AuditLogger;
use App\Support\Identity\SlugGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestion administrative du catalogue : sous-catégories.
 */
class SubcategoryController extends Controller
{
    /**
     * Affiche les sous-catégories et leur catégorie.
     */
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('categories.manage'), 403);

        $search = trim((string) $request->string('search'));
        $categoryId = (string) $request->string('category');
        $active = (string) $request->string('active');

        $paginator = Subcategory::query()
            ->with('category:id,name')
            ->withCount('providerProfiles')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->when($categoryId !== '', fn ($query) => $query->where('category_id', $categoryId))
            ->when($active === '1', fn ($query) => $query->where('is_active', true))
            ->when($active === '0', fn ($query) => $query->where('is_active', false))
            ->orderBy('category_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/Subcategories', [
            'subcategories' => [
                'data' => collect($paginator->items())->map(fn (Subcategory $subcategory) => [
                    'id' => $subcategory->id,
                    'name' => $subcategory->name,
                    'slug' => $subcategory->slug,
                    'description' => $subcategory->description,
                    'is_active' => $subcategory->is_active,
                    'sort_order' => $subcategory->sort_order,
                    'category_id' => $subcategory->category_id,
                    'category' => $subcategory->category?->name,
                    'profiles_count' => $subcategory->provider_profiles_count,
                ])->all(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'search' => $search,
                'category' => $categoryId,
                'active' => $active,
            ],
            'categories' => Category::query()->ordered()->get(['id', 'name'])->toArray(),
        ]);
    }

    /**
     * Crée une sous-catégorie.
     */
    public function store(StoreSubcategoryRequest $request, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->can('categories.manage'), 403);

        $subcategory = Subcategory::create([
            'category_id' => $request->validated('category_id'),
            'name' => $request->validated('name'),
            'slug' => SlugGenerator::unique('subcategories', $request->validated('name')),
            'description' => $request->validated('description'),
            'is_active' => $request->validated('is_active', true),
            'sort_order' => $request->validated('sort_order', 0),
        ]);

        $audit->log(
            'subcategory.created',
            sprintf('Sous-catégorie « %s » créée.', $subcategory->name),
            $subcategory,
            ['name' => $subcategory->name, 'category_id' => $subcategory->category_id],
            $request->user(),
            $request,
        );

        return back()->with('success', 'La sous-catégorie a été créée.');
    }

    /**
     * Modifie une sous-catégorie.
     */
    public function update(UpdateSubcategoryRequest $request, Subcategory $subcategory, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->can('categories.manage'), 403);

        $name = $request->validated('name');

        $subcategory->update([
            'category_id' => $request->validated('category_id'),
            'name' => $name,
            'slug' => SlugGenerator::unique('subcategories', $name, $subcategory->id),
            'description' => $request->validated('description'),
            'is_active' => $request->validated('is_active', $subcategory->is_active),
            'sort_order' => $request->validated('sort_order', $subcategory->sort_order),
        ]);

        $audit->log(
            'subcategory.updated',
            sprintf('Sous-catégorie « %s » modifiée.', $subcategory->name),
            $subcategory,
            ['name' => $subcategory->name, 'category_id' => $subcategory->category_id],
            $request->user(),
            $request,
        );

        return back()->with('success', 'La sous-catégorie a été mise à jour.');
    }

    /**
     * Active ou désactive une sous-catégorie.
     */
    public function updateActivation(Request $request, Subcategory $subcategory, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->can('categories.manage'), 403);

        $validated = $request->validate(['is_active' => ['required', 'boolean']]);

        $subcategory->update(['is_active' => (bool) $validated['is_active']]);

        $audit->log(
            'subcategory.activation.updated',
            sprintf(
                'Sous-catégorie « %s » %s.',
                $subcategory->name,
                $subcategory->is_active ? 'activée' : 'désactivée',
            ),
            $subcategory,
            ['is_active' => $subcategory->is_active],
            $request->user(),
            $request,
        );

        return back()->with('success', 'La visibilité de la sous-catégorie a été mise à jour.');
    }
}
