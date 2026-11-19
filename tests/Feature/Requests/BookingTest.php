<?php

use App\Enums\BookingStatus;
use App\Models\ProviderBooking;
use App\Models\ProviderProfile;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\Requests\BookingScheduler;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Tests des réservations (côté client et côté professionnel).
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

/**
 * Crée un compte client.
 */
function bookingTestCustomer(): User
{
    $user = User::factory()->create();
    $user->assignRole('customer');

    return $user;
}

/**
 * Crée un compte professionnel avec un profil vérifié.
 *
 * @return array{0: User, 1: ProviderProfile}
 */
function bookingTestProfessional(): array
{
    $user = User::factory()->create();
    $user->assignRole('professional');

    $profile = ProviderProfile::factory()->forUser($user)->verified()->create();

    return [$user, $profile];
}

/**
 * Crée une réservation directe pour les besoins du test.
 */
function bookingTestBooking(User $customer, ProviderProfile $profile, Carbon $startsAt, BookingStatus $status = BookingStatus::Pending): ProviderBooking
{
    $booking = ProviderBooking::forceCreate([
        'customer_id' => $customer->id,
        'provider_profile_id' => $profile->id,
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->copy()->addHour(),
        'duration_minutes' => 60,
        'amount' => 15000,
        'address' => 'Bamako, Commune IV',
        'status' => $status,
    ]);

    return $booking;
}

test('le client ne voit que ses propres réservations', function () {
    $customer = bookingTestCustomer();
    $other = bookingTestCustomer();
    [, $profile] = bookingTestProfessional();

    $booking = bookingTestBooking($customer, $profile, now()->addDays(2)->setTime(10, 0));
    bookingTestBooking($other, $profile, now()->addDays(2)->setTime(14, 0));

    $this->actingAs($customer)
        ->get('/reservations')
        ->assertInertia(fn (Assert $page) => $page
            ->component('requests/Bookings')
            ->has('bookings.data', 1)
            ->where('bookings.data.0.id', $booking->id)
            ->where('bookings.data.0.status', 'pending')
        );
});

test('le client peut annuler sa réservation', function () {
    $customer = bookingTestCustomer();
    [, $profile] = bookingTestProfessional();

    $booking = bookingTestBooking($customer, $profile, now()->addDays(2)->setTime(10, 0));

    $this->actingAs($customer)
        ->post("/reservations/{$booking->id}/annuler", ['reason' => 'Empêchement'])
        ->assertSessionHasNoErrors();

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::Cancelled)
        ->and($booking->cancelled_by)->toBe('customer')
        ->and($booking->cancelled_at)->not->toBeNull()
        ->and($booking->cancellation_reason)->toBe('Empêchement');
});

test('un client ne peut pas annuler la réservation d’un autre client', function () {
    $customer = bookingTestCustomer();
    $other = bookingTestCustomer();
    [, $profile] = bookingTestProfessional();

    $booking = bookingTestBooking($customer, $profile, now()->addDays(2)->setTime(10, 0));

    $this->actingAs($other)
        ->post("/reservations/{$booking->id}/annuler")
        ->assertForbidden();

    expect($booking->refresh()->status)->toBe(BookingStatus::Pending);
});

test('le professionnel confirme une réservation en attente', function () {
    $customer = bookingTestCustomer();
    [$professional, $profile] = bookingTestProfessional();

    $booking = bookingTestBooking($customer, $profile, now()->addDays(2)->setTime(10, 0));

    $this->actingAs($professional)
        ->post("/pro/reservations/{$booking->id}/statut", ['status' => 'confirmed'])
        ->assertSessionHasNoErrors();

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::Confirmed)
        ->and($booking->confirmed_at)->not->toBeNull();
});

test('le professionnel clôture une réservation confirmée', function () {
    $customer = bookingTestCustomer();
    [$professional, $profile] = bookingTestProfessional();

    $booking = bookingTestBooking($customer, $profile, now()->addDays(2)->setTime(10, 0), BookingStatus::Confirmed);

    $this->actingAs($professional)
        ->post("/pro/reservations/{$booking->id}/statut", ['status' => 'completed'])
        ->assertSessionHasNoErrors();

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::Completed)
        ->and($booking->completed_at)->not->toBeNull();
});

test('une réservation en attente ne peut pas être clôturée directement', function () {
    $customer = bookingTestCustomer();
    [$professional, $profile] = bookingTestProfessional();

    $booking = bookingTestBooking($customer, $profile, now()->addDays(2)->setTime(10, 0));

    $this->actingAs($professional)
        ->post("/pro/reservations/{$booking->id}/statut", ['status' => 'completed'])
        ->assertSessionHas('error');

    expect($booking->refresh()->status)->toBe(BookingStatus::Pending);
});

test('une réservation terminée ne peut plus être annulée', function () {
    $customer = bookingTestCustomer();
    [$professional, $profile] = bookingTestProfessional();

    $booking = bookingTestBooking($customer, $profile, now()->addDays(2)->setTime(10, 0), BookingStatus::Completed);

    $this->actingAs($professional)
        ->post("/pro/reservations/{$booking->id}/statut", ['status' => 'cancelled'])
        ->assertSessionHas('error');

    expect($booking->refresh()->status)->toBe(BookingStatus::Completed);
});

test('un professionnel ne peut pas piloter la réservation d’un autre professionnel', function () {
    $customer = bookingTestCustomer();
    [, $profile] = bookingTestProfessional();
    [$otherProfessional] = bookingTestProfessional();

    $booking = bookingTestBooking($customer, $profile, now()->addDays(2)->setTime(10, 0));

    $this->actingAs($otherProfessional)
        ->post("/pro/reservations/{$booking->id}/statut", ['status' => 'confirmed'])
        ->assertForbidden();

    expect($booking->refresh()->status)->toBe(BookingStatus::Pending);
});

test('un professionnel ne peut pas annuler la réservation depuis l’espace client', function () {
    $customer = bookingTestCustomer();
    [$professional, $profile] = bookingTestProfessional();

    $booking = bookingTestBooking($customer, $profile, now()->addDays(2)->setTime(10, 0));

    $this->actingAs($professional)
        ->post("/reservations/{$booking->id}/annuler")
        ->assertForbidden();

    expect($booking->refresh()->status)->toBe(BookingStatus::Pending);
});

test('un client ne peut pas piloter une réservation depuis l’espace professionnel', function () {
    $customer = bookingTestCustomer();
    [, $profile] = bookingTestProfessional();

    $booking = bookingTestBooking($customer, $profile, now()->addDays(2)->setTime(10, 0));

    $this->actingAs($customer)
        ->post("/pro/reservations/{$booking->id}/statut", ['status' => 'cancelled'])
        ->assertForbidden();

    expect($booking->refresh()->status)->toBe(BookingStatus::Pending)
        ->and($booking->cancelled_by)->toBeNull();
});

test('le planificateur refuse deux réservations qui se chevauchent', function () {
    $customer = bookingTestCustomer();
    [, $profile] = bookingTestProfessional();

    $startsAt = now()->addDays(3)->setTime(9, 0);

    $first = ServiceRequest::factory()->forCustomer($customer)->create(['provider_profile_id' => $profile->id]);
    $second = ServiceRequest::factory()->forCustomer($customer)->create(['provider_profile_id' => $profile->id]);

    $scheduler = app(BookingScheduler::class);

    $scheduler->book($first, ['starts_at' => $startsAt->format('Y-m-d H:i:s'), 'duration_minutes' => 60]);

    expect(fn () => $scheduler->book($second, [
        'starts_at' => $startsAt->copy()->addMinutes(30)->format('Y-m-d H:i:s'),
        'duration_minutes' => 60,
    ]))->toThrow(ValidationException::class);

    expect(ProviderBooking::count())->toBe(1);
});

test('deux créneaux adjacents sont autorisés', function () {
    $customer = bookingTestCustomer();
    [, $profile] = bookingTestProfessional();

    $startsAt = now()->addDays(3)->setTime(9, 0);

    $first = ServiceRequest::factory()->forCustomer($customer)->create(['provider_profile_id' => $profile->id]);
    $second = ServiceRequest::factory()->forCustomer($customer)->create(['provider_profile_id' => $profile->id]);

    $scheduler = app(BookingScheduler::class);

    $scheduler->book($first, ['starts_at' => $startsAt->format('Y-m-d H:i:s'), 'duration_minutes' => 60]);
    $scheduler->book($second, [
        'starts_at' => $startsAt->copy()->addHour()->format('Y-m-d H:i:s'),
        'duration_minutes' => 60,
    ]);

    expect(ProviderBooking::count())->toBe(2);
});

test('une annulation libère le créneau pour une nouvelle réservation', function () {
    $customer = bookingTestCustomer();
    [, $profile] = bookingTestProfessional();

    $startsAt = now()->addDays(4)->setTime(11, 0);

    $first = ServiceRequest::factory()->forCustomer($customer)->create(['provider_profile_id' => $profile->id]);
    $second = ServiceRequest::factory()->forCustomer($customer)->create(['provider_profile_id' => $profile->id]);

    $scheduler = app(BookingScheduler::class);

    $booking = $scheduler->book($first, ['starts_at' => $startsAt->format('Y-m-d H:i:s'), 'duration_minutes' => 60]);
    $booking->forceFill(['status' => BookingStatus::Cancelled, 'cancelled_at' => now()])->save();

    $newBooking = $scheduler->book($second, [
        'starts_at' => $startsAt->format('Y-m-d H:i:s'),
        'duration_minutes' => 60,
    ]);

    expect($newBooking->status)->toBe(BookingStatus::Pending)
        ->and(ProviderBooking::count())->toBe(2);
});
