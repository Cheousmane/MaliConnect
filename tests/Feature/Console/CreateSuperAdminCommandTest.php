<?php

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('la commande crée un compte super administrateur actif et vérifié', function () {
    $this->artisan('app:create-super-admin', [
        'email' => 'cheousmane@example.com',
        '--name' => 'Cheikh Ousmane',
        '--password' => 'Str0ng!Passw0rd#2026',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'cheousmane@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Cheikh Ousmane');
    expect($user->status)->toBe(AccountStatus::Active);
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->hasRole(UserRole::SuperAdmin->value))->toBeTrue();
    expect(Hash::check('Str0ng!Passw0rd#2026', $user->password))->toBeTrue();
});

test('la commande promeut un compte existant sans dupliquer l’utilisateur', function () {
    $user = User::factory()->create([
        'email' => 'cheousmane@example.com',
        'status' => AccountStatus::Suspended,
        'email_verified_at' => null,
    ]);

    $this->artisan('app:create-super-admin', [
        'email' => 'cheousmane@example.com',
    ])->assertSuccessful();

    expect(User::query()->where('email', 'cheousmane@example.com')->count())->toBe(1);

    $user->refresh();

    expect($user->status)->toBe(AccountStatus::Active);
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->hasRole(UserRole::SuperAdmin->value))->toBeTrue();
});

test('la commande refuse un mot de passe faible', function () {
    $this->artisan('app:create-super-admin', [
        'email' => 'cheousmane@example.com',
        '--password' => 'motdepasse',
    ])->assertFailed();

    expect(User::query()->where('email', 'cheousmane@example.com')->exists())->toBeFalse();
});

test('la commande refuse une adresse e-mail invalide', function () {
    $this->artisan('app:create-super-admin', [
        'email' => 'adresse-invalide',
    ])->assertFailed();

    expect(User::query()->count())->toBe(0);
});
