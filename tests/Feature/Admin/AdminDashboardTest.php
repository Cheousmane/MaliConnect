<?php

use App\Enums\PaymentStatus;
use App\Models\Business;
use App\Models\Payment;
use App\Models\ProviderBooking;
use App\Models\ProviderProfile;
use App\Models\ServiceRequest;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * Vérifie le tableau de bord analytique de l'administration.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

/**
 * Crée un administrateur de test.
 */
function dashboardAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

test('un visiteur est redirigé vers la connexion', function () {
    $this->get('/admin')->assertRedirect('/login');
});

test('un client ne peut pas accéder au tableau de bord administrateur', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $this->actingAs($customer)->get('/admin')->assertForbidden();
});

test('le tableau de bord affiche des zéros sur une base sans activité', function () {
    $admin = dashboardAdmin();

    $this->actingAs($admin)->get('/admin')->assertInertia(fn ($page) => $page
        ->component('admin/Dashboard')
        ->where('revenue.total', 0)
        ->where('pending.profiles', 0)
        ->where('pending.reports', 0)
        ->has('metrics', 9),
    );
});

test('les indicateurs reflètent uniquement les données réelles', function () {
    $admin = dashboardAdmin();
    $profile = ProviderProfile::factory()->verified()->create();
    Business::factory()->create();
    ServiceRequest::factory()->create();
    ProviderBooking::factory()->completed()->create();
    Subscription::factory()->active()->create(['provider_profile_id' => $profile->id]);

    Payment::factory()->paid()->create(['amount' => 15000, 'status' => PaymentStatus::Paid]);
    Payment::factory()->pending()->create(['amount' => 9000]);

    $this->actingAs($admin)->get('/admin')->assertInertia(fn ($page) => $page
        ->where('revenue.total', 15000)
        ->where('metrics.7.value', 1)
        ->where('metrics.8.value', 15000),
    );
});
