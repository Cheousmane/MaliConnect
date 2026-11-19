<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Envoie une nouvelle notification de vérification d'e-mail.
 *
 * La demande est possible avec ou sans session : un visiteur qui a perdu
 * son lien peut saisir son adresse e-mail pour en recevoir un nouveau.
 */
class EmailVerificationNotificationController extends Controller
{
    /**
     * Envoie une nouvelle notification de vérification d'e-mail.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['nullable', 'string', 'email', 'max:255'],
        ]);

        $user = $request->user();

        if ($user === null) {
            $user = User::where('email', $validated['email'] ?? null)->first();
        }

        if ($user !== null && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        // Le message reste identique dans tous les cas : aucune adresse n'est révélée.
        return back()->with('status', 'verification-link-sent');
    }
}
