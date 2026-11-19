<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

/**
 * Vérifie l'adresse e-mail d'un compte à partir du lien signé.
 *
 * Le lien est auto-suffisant : la signature de l'URL et l'empreinte de
 * l'adresse e-mail prouvent la demande. Aucune session active n'est
 * nécessaire, le compte est donc validé depuis n'importe quel appareil.
 */
class VerifyEmailController extends Controller
{
    /**
     * Marque l'adresse e-mail de l'utilisateur comme vérifiée.
     */
    public function __invoke(Request $request, string $id, string $hash): RedirectResponse
    {
        // Signature invalide ou lien expiré : aucun compte n'est modifié.
        if (! URL::hasValidSignature($request)) {
            return redirect()
                ->route('verification.notice')
                ->with('error', 'Ce lien de vérification est invalide ou a expiré. Demandez-en un nouveau.');
        }

        $user = User::find($id);

        // Compte inconnu ou adresse e-mail modifiée depuis l'envoi du lien.
        if ($user === null || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return redirect()
                ->route('verification.notice')
                ->with('error', 'Ce lien de vérification n’est plus valide. Demandez-en un nouveau.');
        }

        if ($user->hasVerifiedEmail()) {
            return $this->redirectToDashboard($request, $user);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->redirectToDashboard($request, $user);
    }

    /**
     * Redirige vers l'espace de travail et connecte l'utilisateur si besoin.
     */
    private function redirectToDashboard(Request $request, User $user): RedirectResponse
    {
        // Le lien peut être ouvert depuis un autre appareil : on connecte alors le compte.
        if (! Auth::check()) {
            Auth::login($user);

            $request->session()->regenerate();
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
