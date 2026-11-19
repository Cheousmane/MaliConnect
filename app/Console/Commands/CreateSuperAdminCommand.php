<?php

namespace App\Console\Commands;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

/**
 * Crée ou promeut un compte super administrateur.
 *
 * Le mot de passe n'est jamais stocké en clair ni journalisé. S'il n'est pas
 * fourni, un mot de passe temporaire est généré et affiché une seule fois.
 */
class CreateSuperAdminCommand extends Command
{
    /**
     * Nom et signature de la commande.
     *
     * @var string
     */
    protected $signature = 'app:create-super-admin
                            {email? : Adresse e-mail du super administrateur}
                            {--name= : Nom affiché du compte}
                            {--password= : Mot de passe (généré automatiquement si absent)}';

    /**
     * Description affichée par `php artisan list`.
     *
     * @var string
     */
    protected $description = 'Crée ou promeut un compte super administrateur';

    /**
     * Exécute la commande.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        if (! is_string($email) || trim($email) === '') {
            if (! $this->input->isInteractive()) {
                $this->error('L’adresse e-mail est obligatoire en mode non interactif.');

                return self::FAILURE;
            }

            $email = $this->ask('Adresse e-mail du super administrateur');
        }

        $email = Str::lower(trim((string) $email));

        $emailValidator = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'email', 'max:255']],
        );

        if ($emailValidator->fails()) {
            $this->error($emailValidator->errors()->first('email'));

            return self::FAILURE;
        }

        // Le rôle doit exister avant toute attribution.
        $roleExists = Role::query()
            ->where('name', UserRole::SuperAdmin->value)
            ->where('guard_name', 'web')
            ->exists();

        if (! $roleExists) {
            $this->call(RoleSeeder::class);
        }

        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null) {
            $existing->forceFill([
                'status' => AccountStatus::Active,
                'email_verified_at' => $existing->email_verified_at ?? now(),
            ])->save();

            $existing->syncRoles([UserRole::SuperAdmin->value]);

            $this->info("Le compte {$email} existe déjà : rôle super administrateur confirmé.");

            return self::SUCCESS;
        }

        $password = $this->option('password');
        $generated = false;

        $passwordRule = Password::min(12)->mixedCase()->numbers()->symbols();

        if (! is_string($password) || $password === '') {
            // Régénérer jusqu'à obtenir un mot de passe conforme à la politique.
            do {
                $password = Str::password(16);
            } while (Validator::make(['password' => $password], ['password' => [$passwordRule]])->fails());

            $generated = true;
        }

        $passwordValidator = Validator::make(
            ['password' => $password],
            ['password' => ['required', $passwordRule]],
        );

        if ($passwordValidator->fails()) {
            $this->error($passwordValidator->errors()->first('password'));
            $this->line('Le mot de passe doit contenir au moins 12 caractères, des majuscules, des minuscules, des chiffres et des symboles.');

            return self::FAILURE;
        }

        $name = $this->option('name');

        if (! is_string($name) || trim($name) === '') {
            $name = $this->input->isInteractive()
                ? (string) $this->ask('Nom affiché', 'Super administrateur')
                : 'Super administrateur';
        }

        $user = User::query()->create([
            'name' => trim($name),
            'email' => $email,
            'password' => $password,
        ]);

        // Le statut et la vérification d'e-mail ne sont pas assignables en masse.
        $user->forceFill([
            'status' => AccountStatus::Active,
            'email_verified_at' => now(),
        ])->save();

        $user->assignRole(UserRole::SuperAdmin->value);

        $this->info('Compte super administrateur créé avec succès.');
        $this->line("E-mail : {$email}");

        if ($generated) {
            $this->warn("Mot de passe temporaire : {$password}");
            $this->line('Conservez-le de façon sécurisée et changez-le après la première connexion.');
        }

        return self::SUCCESS;
    }
}
