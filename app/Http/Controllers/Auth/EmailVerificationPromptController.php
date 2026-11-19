<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Affiche la page d'invitation à vérifier l'adresse e-mail.
 *
 * La page reste accessible sans session : un lien perdu ou expiré peut être
 * renouvelé en saisissant son adresse e-mail.
 */
class EmailVerificationPromptController extends Controller
{
    /**
     * Affiche la page d'invitation à vérifier l'e-mail.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        $user = $request->user();

        if ($user?->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
            'error' => $request->session()->get('error'),
            'email' => $user?->email ?? '',
            'isAuthenticated' => $user !== null,
            'verificationUrl' => $this->developmentUrl($user),
        ]);
    }

    /**
     * En développement, le lien de vérification est proposé directement.
     *
     * Rien n'est affiché en production : le lien transite uniquement par
     * l'e-mail envoyé à l'utilisateur.
     */
    private function developmentUrl(?User $user): ?string
    {
        if ($user === null || ! app()->environment('local') || config('mail.default') !== 'log') {
            return null;
        }

        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );
    }
}
