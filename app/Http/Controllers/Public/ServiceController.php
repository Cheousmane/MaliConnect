<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Support\Catalog\ProfilePresenter;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Fiche publique d'un service proposé.
 */
class ServiceController extends Controller
{
    /**
     * Affiche le détail d'un service actif et vérifié.
     */
    public function show(Service $service): Response
    {
        $service->load(['providerProfile.category', 'providerProfile.business']);

        // Le service n'est public que si son professionnel est vérifié et actif.
        abort_unless($service->is_active, 404);
        abort_unless($service->providerProfile?->isPubliclyVisible(), 404);

        return Inertia::render('public/Service', [
            'service' => ProfilePresenter::service($service),
            'professional' => ProfilePresenter::summary($service->providerProfile),
        ]);
    }
}
