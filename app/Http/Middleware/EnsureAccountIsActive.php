<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloque l'accès à l'application aux comptes non actifs.
 *
 * Un compte suspendu ou clôturé est déconnecté immédiatement et renvoyé
 * vers la page de connexion avec un message explicatif.
 */
class EnsureAccountIsActive
{
    /**
     * Exécute le middleware.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->isActive()) {
            return $next($request);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->withErrors([
                'email' => $user->status === AccountStatus::Suspended
                    ? 'Votre compte a été suspendu. Contactez l’administration pour en savoir plus.'
                    : 'Votre compte n’est plus actif. Contactez l’administration pour en savoir plus.',
            ]);
    }
}
