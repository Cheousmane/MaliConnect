<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateServiceActivationRequest;
use App\Models\Service;
use App\Services\Admin\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Supervision administrative des services publiés par les professionnels.
 */
class ServiceController extends Controller
{
    /**
     * Affiche la liste filtrable des services.
     */
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('services.manage'), 403);

        $search = trim((string) $request->string('search'));
        $active = (string) $request->string('active');

        $paginator = Service::query()
            ->with(['providerProfile:id,business_name,category_id', 'providerProfile.category:id,name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', '%'.$search.'%')
                        ->orWhereHas('providerProfile', fn ($profile) => $profile->where('business_name', 'like', '%'.$search.'%'));
                });
            })
            ->when($active === '1', fn ($query) => $query->where('is_active', true))
            ->when($active === '0', fn ($query) => $query->where('is_active', false))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/Services', [
            'services' => [
                'data' => collect($paginator->items())->map(fn (Service $service) => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'description' => $service->description,
                    'price_type' => $service->price_type,
                    'formatted_price' => $service->formatted_price,
                    'duration_minutes' => $service->duration_minutes,
                    'is_active' => $service->is_active,
                    'professional' => $service->providerProfile?->business_name,
                    'category' => $service->providerProfile?->category?->name,
                    'created_at' => $service->created_at?->toIso8601String(),
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
     * Active ou désactive un service.
     */
    public function updateActivation(
        UpdateServiceActivationRequest $request,
        Service $service,
        AuditLogger $audit,
    ): RedirectResponse {
        abort_unless($request->user()->can('services.manage'), 403);

        $service->update(['is_active' => $request->boolean('is_active')]);

        $audit->log(
            'service.activation.updated',
            sprintf(
                'Service « %s » %s par l’administration.',
                $service->name,
                $service->is_active ? 'activé' : 'désactivé',
            ),
            $service,
            ['is_active' => $service->is_active],
            $request->user(),
            $request,
        );

        return back()->with(
            'success',
            $service->is_active ? 'Le service a été activé.' : 'Le service a été désactivé.',
        );
    }
}
