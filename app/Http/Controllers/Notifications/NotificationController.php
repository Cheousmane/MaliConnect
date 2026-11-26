<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Support\Notifications\NotificationPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Centre de notifications du compte connecté.
 */
class NotificationController extends Controller
{
    /**
     * Affiche l'historique complet des notifications.
     */
    public function index(Request $request): Response
    {
        $notifications = $request->user()->notifications()->latest()->paginate(20);

        return Inertia::render('notifications/Index', [
            'notifications' => [
                'data' => collect($notifications->items())
                    ->map(fn ($notification) => NotificationPresenter::item($notification))
                    ->all(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Marque une notification comme lue puis ouvre la ressource concernée.
     */
    public function read(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        $url = $item->data['url'] ?? null;

        // Convertir les URL absolues en URL relatives pour éviter les
        // problèmes de CORS lorsque l'application est accédée via une
        // adresse différente de celle configurée (127.0.0.1 vs localhost).
        if (is_string($url) && $url !== '') {
            $parsed = parse_url($url);
            if (is_array($parsed) && isset($parsed['path'])) {
                $url = $parsed['path'];
                if (isset($parsed['query'])) {
                    $url .= '?'.$parsed['query'];
                }
            }
        }

        return redirect()->to(is_string($url) && $url !== '' ? $url : route('notifications.index'));
    }

    /**
     * Marque toutes les notifications du compte comme lues.
     */
    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }

    /**
     * Supprime une notification du compte.
     */
    public function destroy(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->whereKey($notification)->delete();

        return back()->with('success', 'La notification a été supprimée.');
    }
}
