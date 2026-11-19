<?php

use App\Enums\AccountStatus;
use App\Models\User;

/**
 * Vérifie que le statut du compte conditionne l'accès à l'application.
 */
test('a suspended account is disconnected and redirected to the login page', function () {
    $user = User::factory()->create(['status' => AccountStatus::Suspended]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('a closed account cannot reach the settings pages', function () {
    $user = User::factory()->create(['status' => AccountStatus::Closed]);

    $response = $this->actingAs($user)->get('/settings/profile');

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});

test('an active account keeps full access', function () {
    $user = User::factory()->create(['status' => AccountStatus::Active]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
});
