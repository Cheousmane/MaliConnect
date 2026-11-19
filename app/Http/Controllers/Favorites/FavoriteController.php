<?php

namespace App\Http\Controllers\Favorites;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\ProviderProfile;
use App\Support\Catalog\ProfilePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Liste des professionnels favoris d'un utilisateur.
 */
class FavoriteController extends Controller
{
    /**
     * Affiche les favoris du compte connecté.
     */
    public function index(Request $request): Response
    {
        $favorites = $request->user()->favorites()
            ->with(['providerProfile' => fn ($query) => $query
                ->with(['category', 'subcategory', 'business'])
                ->withCount(['services', 'media'])])
            ->latest()
            ->paginate(12);

        return Inertia::render('favorites/Index', [
            'favorites' => [
                'data' => collect($favorites->items())
                    ->filter(fn (Favorite $favorite) => $favorite->providerProfile !== null)
                    ->map(fn (Favorite $favorite) => [
                        'id' => $favorite->id,
                        'created_at' => $favorite->created_at?->toIso8601String(),
                        'profile' => ProfilePresenter::summary($favorite->providerProfile),
                    ])
                    ->values()
                    ->all(),
                'current_page' => $favorites->currentPage(),
                'last_page' => $favorites->lastPage(),
                'total' => $favorites->total(),
            ],
        ]);
    }

    /**
     * Ajoute un professionnel aux favoris, sans doublon.
     */
    public function store(Request $request, ProviderProfile $profile): RedirectResponse
    {
        // Seuls les profils publics peuvent être enregistrés.
        abort_unless($profile->isPubliclyVisible(), 404);

        $favorite = $request->user()->favorites()->firstOrCreate([
            'provider_profile_id' => $profile->id,
        ]);

        return back()->with(
            'success',
            $favorite->wasRecentlyCreated
                ? 'Le professionnel a été ajouté à vos favoris.'
                : 'Ce professionnel figure déjà dans vos favoris.',
        );
    }

    /**
     * Retire un professionnel des favoris du compte.
     */
    public function destroy(Request $request, ProviderProfile $profile): RedirectResponse
    {
        $deleted = $request->user()->favorites()
            ->where('provider_profile_id', $profile->id)
            ->delete();

        return back()->with(
            'success',
            $deleted > 0
                ? 'Le professionnel a été retiré de vos favoris.'
                : 'Ce professionnel ne figure pas dans vos favoris.',
        );
    }
}
