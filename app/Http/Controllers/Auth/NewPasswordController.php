<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    /**
     * Affiche la page de réinitialisation du mot de passe.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }

    /**
     * Traite une demande de nouveau mot de passe.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Nous tentons de réinitialiser le mot de passe. En cas de succès, le
        // mot de passe est mis à jour sur le modèle utilisateur et persisté en
        // base. Sinon, l'erreur est analysée et renvoyée dans la réponse.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                // Un compte suspendu ou clôturé ne peut pas réinitialiser son mot de passe.
                if (! $user->isActive()) {
                    throw ValidationException::withMessages([
                        'email' => 'Votre compte n’est pas actif. Contactez l’administration pour en savoir plus.',
                    ]);
                }

                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // Si la réinitialisation réussit, l'utilisateur est redirigé vers la
        // page de connexion. En cas d'erreur, il revient sur son formulaire
        // avec le message approprié.
        if ($status == Password::PasswordReset) {
            return to_route('login')->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
