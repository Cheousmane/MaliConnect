<?php

use App\Enums\QuoteStatus;
use App\Enums\ServiceRequestStatus;
use App\Models\Business;
use App\Models\ProviderBooking;
use App\Models\ProviderProfile;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestQuote;
use App\Models\User;
use App\Notifications\BookingCancelledNotification;
use App\Notifications\BookingConfirmedNotification;
use App\Notifications\NewBookingNotification;
use App\Notifications\NewQuoteNotification;
use App\Notifications\NewReviewNotification;
use App\Notifications\NewServiceRequestNotification;
use App\Notifications\ProfessionalReplyNotification;
use App\Notifications\QuoteAcceptedNotification;
use App\Notifications\QuoteRejectedNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Tests des notifications : déclenchement, destinataires et centre de notifications.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

/**
 * Crée un compte client.
 */
function notificationTestCustomer(): User
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
function notificationTestProfessional(): array
{
    $user = User::factory()->create();
    $user->assignRole('professional');

    $profile = ProviderProfile::factory()->forUser($user)->verified()->create();

    return [$user, $profile];
}

/**
 * Prépare une demande de devis en attente.
 */
function notificationTestPending(User $customer, ProviderProfile $profile): ServiceRequest
{
    return ServiceRequest::factory()
        ->forCustomer($customer)
        ->create(['provider_profile_id' => $profile->id]);
}

/**
 * Crée un devis en attente et place la demande dans l'état correspondant.
 */
function notificationTestQuote(ServiceRequest $request): ServiceRequestQuote
{
    $quote = $request->quotes()->create([
        'provider_profile_id' => $request->provider_profile_id,
        'service_id' => $request->service_id,
        'message' => 'Voici notre proposition détaillée pour cette intervention.',
        'amount' => 25000,
        'duration_minutes' => 60,
        'valid_until' => now()->addDays(7),
    ]);

    $request->forceFill([
        'status' => ServiceRequestStatus::Quoted,
        'responded_at' => now(),
    ])->save();

    return $quote;
}

/**
 * Crée une réservation terminée et éligible à un avis.
 */
function notificationTestCompletedBooking(User $customer, ProviderProfile $profile): ProviderBooking
{
    return ProviderBooking::factory()
        ->forCustomer($customer)
        ->forProfile($profile)
        ->completed()
        ->create();
}

test('une nouvelle demande de devis notifie le gestionnaire du profil', function () {
    Notification::fake();

    $customer = notificationTestCustomer();
    [$professional, $profile] = notificationTestProfessional();

    $this->actingAs($customer)->post('/demandes', [
        'provider_profile_id' => $profile->id,
        'title' => 'Fuite sous évier',
        'description' => 'Une fuite importante sous l’évier de la cuisine doit être réparée rapidement.',
        'city' => 'Bamako',
        'commune' => 'IV',
    ])->assertSessionHasNoErrors();

    Notification::assertSentTo($professional, NewServiceRequestNotification::class);
});

test('une nouvelle demande notifie tous les gestionnaires d’une entreprise', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $owner->assignRole('professional');
    $manager = User::factory()->create();
    $manager->assignRole('professional');

    $business = Business::factory()->forOwner($owner)->verified()->create();
    $business->members()->create([
        'user_id' => $manager->id,
        'role' => 'manager',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $profile = ProviderProfile::factory()
        ->forUser($owner)
        ->verified()
        ->create(['business_id' => $business->id]);

    $customer = notificationTestCustomer();

    $this->actingAs($customer)->post('/demandes', [
        'provider_profile_id' => $profile->id,
        'title' => 'Travaux de peinture',
        'description' => 'Nous souhaitons repeindre deux pièces avant la fin du mois.',
        'city' => 'Bamako',
    ])->assertSessionHasNoErrors();

    Notification::assertSentTo($owner, NewServiceRequestNotification::class);
    Notification::assertSentTo($manager, NewServiceRequestNotification::class);
});

test('un nouveau devis notifie le client', function () {
    Notification::fake();

    $customer = notificationTestCustomer();
    [$professional, $profile] = notificationTestProfessional();
    $serviceRequest = notificationTestPending($customer, $profile);

    $this->actingAs($professional)->post("/pro/demandes/{$serviceRequest->id}/devis", [
        'message' => 'Nous pouvons intervenir dès demain matin avec le matériel nécessaire.',
        'amount' => 35000,
        'duration_minutes' => 90,
    ])->assertSessionHasNoErrors();

    Notification::assertSentTo($customer, NewQuoteNotification::class);
});

test('une réponse du professionnel notifie le client', function () {
    Notification::fake();

    $customer = notificationTestCustomer();
    [$professional, $profile] = notificationTestProfessional();
    $serviceRequest = notificationTestPending($customer, $profile);

    $this->actingAs($professional)->post("/pro/demandes/{$serviceRequest->id}/messages", [
        'body' => 'Pouvez-vous préciser l’étage et la largeur de l’escalier ?',
    ])->assertSessionHasNoErrors();

    Notification::assertSentTo($customer, ProfessionalReplyNotification::class);
});

test('l’acceptation d’un devis notifie le professionnel et crée la réservation', function () {
    Notification::fake();

    $customer = notificationTestCustomer();
    [$professional, $profile] = notificationTestProfessional();
    $serviceRequest = notificationTestPending($customer, $profile);
    $quote = notificationTestQuote($serviceRequest);

    $this->actingAs($customer)->post("/demandes/{$serviceRequest->id}/devis/{$quote->id}/accepter", [
        'starts_at' => now()->addDays(3)->setTime(10, 0)->format('Y-m-d H:i:s'),
        'duration_minutes' => 60,
    ])->assertSessionHasNoErrors();

    Notification::assertSentTo($professional, QuoteAcceptedNotification::class);
    Notification::assertSentTo($professional, NewBookingNotification::class);
});

test('le refus d’un devis notifie le professionnel et rouvre la demande', function () {
    Notification::fake();

    $customer = notificationTestCustomer();
    [$professional, $profile] = notificationTestProfessional();
    $serviceRequest = notificationTestPending($customer, $profile);
    $quote = notificationTestQuote($serviceRequest);

    $this->actingAs($customer)
        ->post("/demandes/{$serviceRequest->id}/devis/{$quote->id}/refuser", [
            'reason' => 'Le tarif dépasse notre budget.',
        ])
        ->assertSessionHasNoErrors();

    expect($quote->refresh()->status)->toBe(QuoteStatus::Rejected)
        ->and($serviceRequest->refresh()->status)->toBe(ServiceRequestStatus::InProgress);

    Notification::assertSentTo($professional, QuoteRejectedNotification::class);
});

test('un nouvel avis notifie le gestionnaire du profil', function () {
    Notification::fake();

    $customer = notificationTestCustomer();
    [$professional, $profile] = notificationTestProfessional();
    $booking = notificationTestCompletedBooking($customer, $profile);

    $this->actingAs($customer)->post("/reservations/{$booking->id}/avis", [
        'rating' => 5,
        'comment' => 'Prestation impeccable et très professionnelle.',
    ])->assertSessionHasNoErrors();

    Notification::assertSentTo($professional, NewReviewNotification::class);
});

test('la confirmation et l’annulation d’une réservation notifient le client et le professionnel', function () {
    Notification::fake();

    $customer = notificationTestCustomer();
    [$professional, $profile] = notificationTestProfessional();

    $booking = ProviderBooking::factory()
        ->forCustomer($customer)
        ->forProfile($profile)
        ->create();

    $this->actingAs($professional)
        ->post("/pro/reservations/{$booking->id}/statut", ['status' => 'confirmed'])
        ->assertSessionHasNoErrors();

    Notification::assertSentTo($customer, BookingConfirmedNotification::class);

    $this->actingAs($customer)
        ->post("/reservations/{$booking->id}/annuler", ['reason' => 'Empêchement personnel'])
        ->assertSessionHasNoErrors();

    Notification::assertSentTo($professional, BookingCancelledNotification::class);
});

test('le centre de notifications affiche et marque les notifications du compte', function () {
    $customer = notificationTestCustomer();
    [, $profile] = notificationTestProfessional();
    $serviceRequest = notificationTestPending($customer, $profile);
    $quote = notificationTestQuote($serviceRequest);

    $customer->notify(new NewQuoteNotification($serviceRequest, $quote));

    $this->actingAs($customer)
        ->get('/notifications')
        ->assertInertia(fn (Assert $page) => $page
            ->component('notifications/Index')
            ->has('notifications.data', 1)
            ->where('notifications.data.0.title', 'Nouveau devis reçu')
            ->where('notifications.data.0.read', false)
            ->where('unreadCount', 1)
        );

    $notification = $customer->notifications()->firstOrFail();

    $this->actingAs($customer)
        ->post("/notifications/{$notification->id}/lire")
        ->assertRedirect(route('requests.show', $serviceRequest));

    expect($notification->refresh()->read_at)->not->toBeNull();
});

test('toutes les notifications peuvent être marquées comme lues', function () {
    $customer = notificationTestCustomer();
    [, $profile] = notificationTestProfessional();
    $serviceRequest = notificationTestPending($customer, $profile);
    $quote = notificationTestQuote($serviceRequest);

    $customer->notify(new NewQuoteNotification($serviceRequest, $quote));

    $this->actingAs($customer)
        ->post('/notifications/tout-lire')
        ->assertSessionHasNoErrors();

    expect($customer->unreadNotifications()->count())->toBe(0);
});

test('un utilisateur ne peut pas lire la notification d’un autre compte', function () {
    $customer = notificationTestCustomer();
    $other = notificationTestCustomer();
    [, $profile] = notificationTestProfessional();
    $serviceRequest = notificationTestPending($customer, $profile);
    $quote = notificationTestQuote($serviceRequest);

    $customer->notify(new NewQuoteNotification($serviceRequest, $quote));
    $notification = $customer->notifications()->firstOrFail();

    $this->actingAs($other)
        ->post("/notifications/{$notification->id}/lire")
        ->assertNotFound();

    expect($notification->refresh()->read_at)->toBeNull();
});

test('le compteur de notifications non lues est partagé avec l’interface', function () {
    $customer = notificationTestCustomer();
    [, $profile] = notificationTestProfessional();
    $serviceRequest = notificationTestPending($customer, $profile);
    $quote = notificationTestQuote($serviceRequest);

    $customer->notify(new NewQuoteNotification($serviceRequest, $quote));

    $this->actingAs($customer)
        ->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->where('unreadNotificationsCount', 1)
            ->has('notificationsPreview', 1)
        );
});
