<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    Notification::fake();

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('verification.notice'));

    $user = User::where('email', 'test@example.com')->firstOrFail();

    expect($user->name)->toBe('Test User');
    expect($user->status->value)->toBe('active');
});

test('registration sends an email verification link', function () {
    Notification::fake();

    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    Notification::assertSentTo(User::where('email', 'test@example.com')->firstOrFail(), VerifyEmail::class);
});

test('a registered user only receives the customer role', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $user = User::where('email', 'test@example.com')->firstOrFail();

    expect($user->getRoleNames()->all())->toBe(['customer']);
    expect($user->hasRole('admin'))->toBeFalse();
    expect($user->hasRole('super-admin'))->toBeFalse();
    expect($user->can('users.viewAny'))->toBeFalse();
    expect($user->can('users.assign_roles'))->toBeFalse();
    expect($user->can('admin.access'))->toBeFalse();
});

test('registration rejects a duplicate email address', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSessionHasErrors('email');

    expect(User::count())->toBe(1);
});

test('registration rejects an invalid phone number', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '12345',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSessionHasErrors('phone');

    expect(User::count())->toBe(0);
});

test('registration normalizes a malian phone number', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '76 12 34 56',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $user = User::where('email', 'test@example.com')->firstOrFail();

    expect($user->phone)->toBe('+22376123456');
});

test('registration accepts any eight digit phone number', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '20 12 34 56',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $user = User::where('email', 'test@example.com')->firstOrFail();

    expect($user->phone)->toBe('+22320123456');
});

test('registration rejects a phone number that is not eight digits', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '1234567',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSessionHasErrors('phone');

    expect(User::count())->toBe(0);
});

test('a professional registration assigns the professional role', function () {
    $response = $this->post('/register', [
        'name' => 'Professionnel Indépendant',
        'email' => 'pro@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'plan' => 'pro',
    ]);

    $response->assertRedirect(route('verification.notice'));

    $user = User::where('email', 'pro@example.com')->firstOrFail();

    expect($user->hasRole('professional'))->toBeTrue()
        ->and($user->hasRole('customer'))->toBeFalse()
        ->and($user->can('provider_profiles.create'))->toBeTrue()
        ->and($user->can('businesses.create'))->toBeFalse();
});

test('a business registration assigns the company manager role', function () {
    $response = $this->post('/register', [
        'name' => 'Gestionnaire Entreprise',
        'email' => 'entreprise@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'plan' => 'business',
    ]);

    $response->assertRedirect(route('verification.notice'));

    $user = User::where('email', 'entreprise@example.com')->firstOrFail();

    expect($user->hasRole('company-manager'))->toBeTrue()
        ->and($user->hasRole('professional'))->toBeFalse()
        ->and($user->can('businesses.create'))->toBeTrue()
        ->and($user->can('provider_profiles.create'))->toBeTrue();
});
