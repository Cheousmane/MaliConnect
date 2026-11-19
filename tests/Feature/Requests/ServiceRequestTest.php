<?php

use App\Enums\AccountStatus;
use App\Enums\BusinessStatus;
use App\Enums\QuoteStatus;
use App\Enums\ServiceRequestStatus;
use App\Models\Business;
use App\Models\ProviderProfile;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestQuote;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Tests des demandes de devis (côté client et côté professionnel).
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

/**
 * Crée un compte client.
 */
function requestTestCustomer(): User
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
function requestTestProfessional(): array
{
    $user = User::factory()->create();
    $user->assignRole('professional');

    $profile = ProviderProfile::factory()->forUser($user)->verified()->create();

    return [$user, $profile];
}

/**
 * Prépare une demande en attente pour un profil donné.
 */
function requestTestPending(User $customer, ProviderProfile $profile): ServiceRequest
{
    return ServiceRequest::factory()
        ->forCustomer($customer)
        ->create(['provider_profile_id' => $profile->id]);
}

/**
 * Crée un devis en attente sur une demande.
 */
function requestTestQuote(ServiceRequest $request, int $amount = 25000): ServiceRequestQuote
{
    $quote = $request->quotes()->create([
        'provider_profile_id' => $request->provider_profile_id,
        'service_id' => $request->service_id,
        'message' => 'Voici notre proposition détaillée pour cette intervention.',
        'amount' => $amount,
        'duration_minutes' => 60,
        'valid_until' => now()->addDays(7),
    ]);

    // Un devis envoyé place la demande dans l'état correspondant.
    $request->forceFill([
        'status' => ServiceRequestStatus::Quoted,
        'responded_at' => now(),
    ])->save();

    return $quote;
}

test('un client peut envoyer une demande de devis avec une pièce jointe', function () {
    Storage::fake('local');

    $customer = requestTestCustomer();
    [, $profile] = requestTestProfessional();
    $service = $profile->services()->create(['name' => 'Réparation', 'price_type' => 'quote']);

    $response = $this->actingAs($customer)->post('/demandes', [
        'provider_profile_id' => $profile->id,
        'service_id' => $service->id,
        'title' => 'Fuite sous évier',
        'description' => 'Une fuite importante sous l’évier de la cuisine doit être réparée rapidement.',
        'address' => 'Rue 12, porte 4',
        'city' => 'Bamako',
        'commune' => 'IV',
        'quartier' => 'Badalabougou',
        'budget_min' => 10000,
        'budget_max' => 30000,
        'desired_date' => now()->addDays(5)->toDateString(),
        'preferred_time' => 'Matin',
        'attachments' => [UploadedFile::fake()->create('devis.pdf', 120, 'application/pdf')],
    ]);

    $serviceRequest = ServiceRequest::firstOrFail();

    $response->assertRedirect(route('requests.show', $serviceRequest));

    expect($serviceRequest->customer_id)->toBe($customer->id)
        ->and($serviceRequest->provider_profile_id)->toBe($profile->id)
        ->and($serviceRequest->service_id)->toBe($service->id)
        ->and($serviceRequest->status)->toBe(ServiceRequestStatus::Pending)
        ->and($serviceRequest->reference)->toStartWith('DM-');

    expect($serviceRequest->attachments)->toHaveCount(1);
    Storage::disk('local')->assertExists($serviceRequest->attachments->first()->path);
});

test('le client propriétaire télécharge la pièce jointe de sa demande', function () {
    Storage::fake('local');

    $customer = requestTestCustomer();
    [, $profile] = requestTestProfessional();
    $serviceRequest = requestTestPending($customer, $profile);

    Storage::disk('local')->put('service-requests/devis.pdf', 'contenu');

    $attachment = $serviceRequest->attachments()->create([
        'path' => 'service-requests/devis.pdf',
        'disk' => 'local',
        'name' => 'devis.pdf',
        'type' => 'document',
        'mime_type' => 'application/pdf',
        'size' => 7,
    ]);

    $this->actingAs($customer)
        ->get(route('requests.attachments.download', [$serviceRequest, $attachment]))
        ->assertOk()
        ->assertDownload('devis.pdf');
});

test('un autre client ne peut pas télécharger la pièce jointe', function () {
    Storage::fake('local');

    $owner = requestTestCustomer();
    $intruder = requestTestCustomer();
    [, $profile] = requestTestProfessional();
    $serviceRequest = requestTestPending($owner, $profile);

    Storage::disk('local')->put('service-requests/prive.pdf', 'contenu');

    $attachment = $serviceRequest->attachments()->create([
        'path' => 'service-requests/prive.pdf',
        'disk' => 'local',
        'name' => 'prive.pdf',
        'type' => 'document',
        'mime_type' => 'application/pdf',
        'size' => 7,
    ]);

    $this->actingAs($intruder)
        ->get(route('requests.attachments.download', [$serviceRequest, $attachment]))
        ->assertForbidden();
});

test('une pièce jointe ne peut pas être téléchargée depuis une autre demande', function () {
    Storage::fake('local');

    $customer = requestTestCustomer();
    [, $profile] = requestTestProfessional();
    $ownedRequest = requestTestPending($customer, $profile);
    $otherRequest = requestTestPending($customer, $profile);

    Storage::disk('local')->put('service-requests/autre.pdf', 'contenu');

    $attachment = $otherRequest->attachments()->create([
        'path' => 'service-requests/autre.pdf',
        'disk' => 'local',
        'name' => 'autre.pdf',
        'type' => 'document',
        'mime_type' => 'application/pdf',
        'size' => 7,
    ]);

    $this->actingAs($customer)
        ->get(route('requests.attachments.download', [$ownedRequest, $attachment]))
        ->assertNotFound();
});

test('une demande ne peut pas viser un profil non vérifié', function () {
    $customer = requestTestCustomer();
    $profile = ProviderProfile::factory()->pending()->create();

    $this->actingAs($customer)
        ->post('/demandes', [
            'provider_profile_id' => $profile->id,
            'title' => 'Demande invalide',
            'description' => 'Cette demande vise un profil qui n’est pas encore vérifié.',
            'city' => 'Bamako',
        ])
        ->assertSessionHasErrors('provider_profile_id');

    expect(ServiceRequest::count())->toBe(0);
});

test('une demande ne peut pas viser le profil d un compte suspendu', function () {
    $customer = requestTestCustomer();
    [$professional, $profile] = requestTestProfessional();

    $professional->forceFill(['status' => AccountStatus::Suspended])->save();

    $this->actingAs($customer)
        ->post('/demandes', [
            'provider_profile_id' => $profile->id,
            'title' => 'Demande vers un compte suspendu',
            'description' => 'Cette demande vise le profil d’un compte qui vient d’être suspendu.',
            'city' => 'Bamako',
        ])
        ->assertNotFound();

    expect(ServiceRequest::count())->toBe(0);
});

test('une demande ne peut pas viser le profil d une entreprise suspendue', function () {
    $customer = requestTestCustomer();
    $business = Business::factory()->verified()->create();
    $profile = ProviderProfile::factory()->verified()->create(['business_id' => $business->id]);

    $business->forceFill(['status' => BusinessStatus::Suspended])->save();

    $this->actingAs($customer)
        ->post('/demandes', [
            'provider_profile_id' => $profile->id,
            'title' => 'Demande vers une entreprise suspendue',
            'description' => 'Cette demande vise une entreprise qui vient d’être suspendue.',
            'city' => 'Bamako',
        ])
        ->assertNotFound();

    expect(ServiceRequest::count())->toBe(0);
});

test('un client ne peut pas consulter la demande d’un autre client', function () {
    $customer = requestTestCustomer();
    $other = requestTestCustomer();
    [, $profile] = requestTestProfessional();

    $serviceRequest = requestTestPending($customer, $profile);

    $this->actingAs($other)
        ->get("/demandes/{$serviceRequest->id}")
        ->assertForbidden();
});

test('le professionnel destinataire peut consulter la demande et envoyer un devis', function () {
    $customer = requestTestCustomer();
    [$professional, $profile] = requestTestProfessional();

    $serviceRequest = requestTestPending($customer, $profile);

    $this->actingAs($professional)
        ->get("/pro/demandes/{$serviceRequest->id}")
        ->assertOk();

    $this->actingAs($professional)
        ->post("/pro/demandes/{$serviceRequest->id}/devis", [
            'message' => 'Nous pouvons intervenir dès demain matin avec le matériel nécessaire.',
            'amount' => 35000,
            'duration_minutes' => 90,
            'valid_until' => now()->addDays(10)->toDateString(),
        ])
        ->assertSessionHasNoErrors();

    $serviceRequest->refresh();

    expect($serviceRequest->status)->toBe(ServiceRequestStatus::Quoted)
        ->and($serviceRequest->quotes)->toHaveCount(1);

    $quote = $serviceRequest->quotes->first();

    expect((float) $quote->amount)->toBe(35000.0)
        ->and($quote->duration_minutes)->toBe(90)
        ->and($quote->status)->toBe(QuoteStatus::Pending)
        ->and($quote->reference)->toStartWith('DV-');
});

test('un professionnel ne peut pas répondre à la demande d’un autre professionnel', function () {
    $customer = requestTestCustomer();
    [, $profile] = requestTestProfessional();
    [$otherProfessional] = requestTestProfessional();

    $serviceRequest = requestTestPending($customer, $profile);

    $this->actingAs($otherProfessional)
        ->get("/pro/demandes/{$serviceRequest->id}")
        ->assertForbidden();

    $this->actingAs($otherProfessional)
        ->post("/pro/demandes/{$serviceRequest->id}/devis", [
            'message' => 'Proposition qui ne devrait jamais être enregistrée par ce compte.',
            'amount' => 20000,
        ])
        ->assertForbidden();

    expect(ServiceRequestQuote::count())->toBe(0);
});

test('le client accepte un devis et une réservation est créée', function () {
    $customer = requestTestCustomer();
    [, $profile] = requestTestProfessional();

    $serviceRequest = requestTestPending($customer, $profile);
    $quote = requestTestQuote($serviceRequest);

    $startsAt = now()->addDays(3)->setTime(10, 0);

    $this->actingAs($customer)
        ->post("/demandes/{$serviceRequest->id}/devis/{$quote->id}/accepter", [
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'duration_minutes' => 60,
        ])
        ->assertRedirect(route('requests.show', $serviceRequest));

    $serviceRequest->refresh();
    $quote->refresh();

    expect($quote->status)->toBe(QuoteStatus::Accepted)
        ->and($serviceRequest->status)->toBe(ServiceRequestStatus::Accepted)
        ->and($serviceRequest->bookings)->toHaveCount(1);

    $booking = $serviceRequest->bookings->first();

    expect($booking->customer_id)->toBe($customer->id)
        ->and($booking->reference)->toStartWith('RV-')
        ->and((float) $booking->amount)->toBe(25000.0)
        ->and($booking->duration_minutes)->toBe(60)
        ->and($booking->starts_at->equalTo($startsAt))->toBeTrue();
});

test('un devis ne peut pas être accepté par un autre client', function () {
    $customer = requestTestCustomer();
    $other = requestTestCustomer();
    [, $profile] = requestTestProfessional();

    $serviceRequest = requestTestPending($customer, $profile);
    $quote = requestTestQuote($serviceRequest);

    $this->actingAs($other)
        ->post("/demandes/{$serviceRequest->id}/devis/{$quote->id}/accepter", [
            'starts_at' => now()->addDays(2)->setTime(9, 0)->format('Y-m-d H:i:s'),
        ])
        ->assertForbidden();

    expect($quote->refresh()->status)->toBe(QuoteStatus::Pending);
});

test('une double réservation sur un même créneau est refusée', function () {
    $customer = requestTestCustomer();
    [, $profile] = requestTestProfessional();

    $firstRequest = requestTestPending($customer, $profile);
    $firstQuote = requestTestQuote($firstRequest);

    $startsAt = now()->addDays(4)->setTime(10, 0);

    $this->actingAs($customer)
        ->post("/demandes/{$firstRequest->id}/devis/{$firstQuote->id}/accepter", [
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'duration_minutes' => 60,
        ])
        ->assertSessionHasNoErrors();

    $secondRequest = requestTestPending($customer, $profile);
    $secondQuote = requestTestQuote($secondRequest);

    // Le second créneau chevauche le premier de trente minutes.
    $this->actingAs($customer)
        ->post("/demandes/{$secondRequest->id}/devis/{$secondQuote->id}/accepter", [
            'starts_at' => $startsAt->copy()->addMinutes(30)->format('Y-m-d H:i:s'),
            'duration_minutes' => 60,
        ])
        ->assertSessionHasErrors('starts_at');

    expect($profile->bookings()->count())->toBe(1)
        ->and($secondRequest->refresh()->status)->toBe(ServiceRequestStatus::Quoted)
        ->and($secondQuote->refresh()->status)->toBe(QuoteStatus::Pending);
});

test('une réservation est refusée lorsque le professionnel est fermé ce jour-là', function () {
    $customer = requestTestCustomer();
    [, $profile] = requestTestProfessional();

    $startsAt = now()->addDays(5)->setTime(10, 0);

    $profile->hours()->create([
        'day_of_week' => $startsAt->dayOfWeek,
        'opens_at' => '08:00',
        'closes_at' => '18:00',
        'is_closed' => true,
    ]);

    $serviceRequest = requestTestPending($customer, $profile);
    $quote = requestTestQuote($serviceRequest);

    $this->actingAs($customer)
        ->post("/demandes/{$serviceRequest->id}/devis/{$quote->id}/accepter", [
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'duration_minutes' => 60,
        ])
        ->assertSessionHasErrors('starts_at');

    expect($profile->bookings()->count())->toBe(0);
});

test('une réservation hors des disponibilités ponctuelles est refusée', function () {
    $customer = requestTestCustomer();
    [, $profile] = requestTestProfessional();

    $startsAt = now()->addDays(6)->setTime(10, 0);

    $profile->availabilities()->create([
        'date' => $startsAt->toDateString(),
        'starts_at' => '14:00',
        'ends_at' => '17:00',
        'is_available' => true,
    ]);

    $serviceRequest = requestTestPending($customer, $profile);
    $quote = requestTestQuote($serviceRequest);

    $this->actingAs($customer)
        ->post("/demandes/{$serviceRequest->id}/devis/{$quote->id}/accepter", [
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'duration_minutes' => 60,
        ])
        ->assertSessionHasErrors('starts_at');

    expect($profile->bookings()->count())->toBe(0);
});

test('le professionnel peut refuser une demande', function () {
    $customer = requestTestCustomer();
    [$professional, $profile] = requestTestProfessional();

    $serviceRequest = requestTestPending($customer, $profile);

    $this->actingAs($professional)
        ->post("/pro/demandes/{$serviceRequest->id}/statut", ['status' => 'refused'])
        ->assertSessionHasNoErrors();

    $serviceRequest->refresh();

    expect($serviceRequest->status)->toBe(ServiceRequestStatus::Refused)
        ->and($serviceRequest->closed_at)->not->toBeNull();
});

test('une transition de statut non autorisée est rejetée', function () {
    $customer = requestTestCustomer();
    [$professional, $profile] = requestTestProfessional();

    $serviceRequest = requestTestPending($customer, $profile);

    $this->actingAs($professional)
        ->post("/pro/demandes/{$serviceRequest->id}/statut", ['status' => 'accepted'])
        ->assertSessionHas('error');

    expect($serviceRequest->refresh()->status)->toBe(ServiceRequestStatus::Pending);
});

test('le client peut annuler une demande ouverte', function () {
    $customer = requestTestCustomer();
    [, $profile] = requestTestProfessional();

    $serviceRequest = requestTestPending($customer, $profile);

    $this->actingAs($customer)
        ->post("/demandes/{$serviceRequest->id}/annuler")
        ->assertSessionHasNoErrors();

    expect($serviceRequest->refresh()->status)->toBe(ServiceRequestStatus::Cancelled);
});

test('une demande terminée ne peut plus être annulée', function () {
    $customer = requestTestCustomer();
    [, $profile] = requestTestProfessional();

    $serviceRequest = requestTestPending($customer, $profile);
    $serviceRequest->forceFill([
        'status' => ServiceRequestStatus::Completed,
        'closed_at' => now(),
    ])->save();

    $this->actingAs($customer)
        ->post("/demandes/{$serviceRequest->id}/annuler")
        ->assertForbidden();
});

test('le client et le professionnel échangent des messages sur la demande', function () {
    $customer = requestTestCustomer();
    [$professional, $profile] = requestTestProfessional();

    $serviceRequest = requestTestPending($customer, $profile);

    $this->actingAs($customer)
        ->post("/demandes/{$serviceRequest->id}/messages", [
            'body' => 'Pouvez-vous intervenir avant vendredi ?',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($professional)
        ->post("/pro/demandes/{$serviceRequest->id}/messages", [
            'body' => 'Oui, je peux passer jeudi en fin de matinée.',
        ])
        ->assertSessionHasNoErrors();

    expect($serviceRequest->messages()->count())->toBe(2);
});

test('aucun message ne peut être ajouté à une demande close', function () {
    $customer = requestTestCustomer();
    [$professional, $profile] = requestTestProfessional();

    $serviceRequest = requestTestPending($customer, $profile);
    $serviceRequest->forceFill([
        'status' => ServiceRequestStatus::Cancelled,
        'closed_at' => now(),
    ])->save();

    $this->actingAs($customer)
        ->post("/demandes/{$serviceRequest->id}/messages", ['body' => 'Message tardif'])
        ->assertSessionHas('error');

    $this->actingAs($professional)
        ->post("/pro/demandes/{$serviceRequest->id}/messages", ['body' => 'Message tardif'])
        ->assertForbidden();

    expect($serviceRequest->messages()->count())->toBe(0);
});

test('un client ne peut pas envoyer de devis', function () {
    $customer = requestTestCustomer();
    [, $profile] = requestTestProfessional();

    $serviceRequest = requestTestPending($customer, $profile);

    $this->actingAs($customer)
        ->post("/pro/demandes/{$serviceRequest->id}/devis", [
            'message' => 'Un client ne doit jamais pouvoir envoyer un devis.',
            'amount' => 10000,
        ])
        ->assertForbidden();
});
