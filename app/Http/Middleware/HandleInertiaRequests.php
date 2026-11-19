<?php

namespace App\Http\Middleware;

use App\Support\Notifications\NotificationPresenter;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * Gabarit racine chargé lors de la première visite.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Détermine la version courante des assets.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Définit les props partagées par défaut avec toutes les pages.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'roles' => $user?->getRoleNames()->values()->all() ?? [],
                'permissions' => $user?->getAllPermissions()->pluck('name')->values()->all() ?? [],
            ],
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'notificationsPreview' => fn () => $user?->notifications()
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (DatabaseNotification $notification) => NotificationPresenter::item($notification))
                ->values()
                ->all() ?? [],
            'unreadNotificationsCount' => fn () => $user?->unreadNotifications()->count() ?? 0,
        ]);
    }
}
