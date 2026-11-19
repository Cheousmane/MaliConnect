<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertStatus(200);
});

test('email verification screen is available without a session', function () {
    $response = $this->get('/verify-email');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('auth/VerifyEmail')
        ->where('isAuthenticated', false),
    );
});

test('email can be verified', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

test('email can be verified from another device without a session', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $response = $this->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')],
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    $response->assertRedirect(route('verification.notice'));
});

test('an invalid verification link returns to a page offering a new link', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')],
    );

    $response = $this->get($verificationUrl);

    $response->assertRedirect(route('verification.notice'));
    $response->assertSessionHas('error');
});

test('a tampered verification link is refused without touching the account', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    // L'empreinte de l'adresse e-mail est falsifiée : la signature ne correspond plus.
    $tampered = preg_replace(
        '/\/verify-email\/\d+\/[0-9a-f]+/',
        '/verify-email/'.$user->id.'/'.str_repeat('0', 40),
        $verificationUrl,
    );

    $response = $this->get($tampered);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    $response->assertRedirect(route('verification.notice'));
    $response->assertSessionHas('error');
});

test('an expired verification link is refused', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->subMinutes(5),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $response = $this->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    $response->assertRedirect(route('verification.notice'));
    $response->assertSessionHas('error');
});

test('a visitor can request a new verification link with an email address', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $response = $this->post('/email/verification-notification', ['email' => $user->email]);

    $response->assertSessionHas('status', 'verification-link-sent');

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('a request for an unknown email address does not reveal anything', function () {
    Notification::fake();

    $response = $this->post('/email/verification-notification', ['email' => 'inconnu@exemple.com']);

    $response->assertSessionHas('status', 'verification-link-sent');

    Notification::assertNothingSent();
});

test('an already verified account is redirected to the dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertRedirect(route('dashboard', absolute: false));
});
