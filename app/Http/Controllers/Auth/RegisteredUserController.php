<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\MaliPhoneNumber;
use App\Services\Identity\IdentityProvisioner;
use App\Support\Identity\PermissionCatalogue;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Affiche la page d'inscription.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Register', [
            'plan' => $request->query('plan'),
        ]);
    }

    /**
     * Traite une demande d'inscription.
     *
     * Un compte particulier reçoit le rôle « client ». Un compte professionnel
     * reçoit le rôle correspondant à son plan : « professional » pour un
     * indépendant, « company-manager » pour une entreprise. Aucune permission
     * d'administration n'est attribuée automatiquement.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['nullable', 'string', 'max:20', new MaliPhoneNumber],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'plan' => ['nullable', 'string', 'in:pro,business'],
        ]);

        $plan = $validated['plan'] ?? null;
        $isProfessional = in_array($plan, ['pro', 'business'], true);
        $role = $plan === 'business' ? 'company-manager' : 'professional';

        $user = DB::transaction(function () use ($validated, $isProfessional, $role) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => isset($validated['phone']) && $validated['phone'] !== ''
                    ? MaliPhoneNumber::normalize($validated['phone'])
                    : null,
            ]);

            if ($isProfessional) {
                IdentityProvisioner::ensureRole($role, PermissionCatalogue::forRole($role));
                $user->assignRole($role);
            } else {
                // Rôle par défaut d'un compte particulier, sans droit d'administration.
                IdentityProvisioner::assignDefaultRole($user);
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        // L'accès à l'application reste conditionné à la vérification de l'e-mail.
        if ($isProfessional) {
            return $user->hasVerifiedEmail()
                ? to_route($role === 'company-manager' ? 'pro.businesses.create' : 'pro.profiles.create')
                : to_route('verification.notice');
        }

        return $user->hasVerifiedEmail()
            ? to_route('dashboard')
            : to_route('verification.notice');
    }
}
