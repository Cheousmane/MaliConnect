<?php

use App\Enums\ReviewStatus;
use App\Models\ProviderBooking;
use App\Models\ProviderProfile;
use App\Models\ProviderReview;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Tests des avis clients : éligibilité, unicité, modération et réponse.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

/**
 * Crée un compte client.
 */
function reviewTestCustomer(): User
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
function reviewTestProfessional(): array
{
    $user = User::factory()->create();
    $user->assignRole('professional');

    $profile = ProviderProfile::factory()->forUser($user)->verified()->create();

    return [$user, $profile];
}

/**
 * Crée un compte administrateur habilité à modérer les avis.
 */
function reviewTestAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

/**
 * Crée une réservation réellement terminée et donc éligible à un avis.
 */
function reviewTestCompletedBooking(User $customer, ProviderProfile $profile): ProviderBooking
{
    return ProviderBooking::factory()
        ->forCustomer($customer)
        ->forProfile($profile)
        ->completed()
        ->create();
}

/**
 * Crée un avis directement en base pour les tests de modération.
 */
function reviewTestReview(
    ProviderBooking $booking,
    int $rating = 4,
    ReviewStatus $status = ReviewStatus::Pending,
): ProviderReview {
    return ProviderReview::factory()->create([
        'provider_booking_id' => $booking->id,
        'customer_id' => $booking->customer_id,
        'provider_profile_id' => $booking->provider_profile_id,
        'rating' => $rating,
        'comment' => 'Prestation sérieuse, ponctuelle et très soignée.',
        'status' => $status,
        'published_at' => $status === ReviewStatus::Approved ? now() : null,
    ]);
}

test('un client dépose un avis sur une prestation terminée', function () {
    $customer = reviewTestCustomer();
    [, $profile] = reviewTestProfessional();

    $booking = reviewTestCompletedBooking($customer, $profile);

    $this->actingAs($customer)
        ->post("/reservations/{$booking->id}/avis", [
            'rating' => 5,
            'comment' => 'Prestation impeccable et très professionnelle.',
        ])
        ->assertRedirect(route('reviews.index'))
        ->assertSessionHasNoErrors();

    $review = ProviderReview::firstOrFail();

    expect($review->provider_booking_id)->toBe($booking->id)
        ->and($review->customer_id)->toBe($customer->id)
        ->and($review->provider_profile_id)->toBe($profile->id)
        ->and($review->rating)->toBe(5)
        ->and($review->status)->toBe(ReviewStatus::Pending)
        ->and($review->published_at)->toBeNull();

    // Un avis en attente ne modifie pas encore la note publique.
    expect($profile->refresh()->rating_count)->toBe(0);
});

test('une réservation non terminée ne peut pas être évaluée', function () {
    $customer = reviewTestCustomer();
    [, $profile] = reviewTestProfessional();

    $booking = ProviderBooking::factory()
        ->forCustomer($customer)
        ->forProfile($profile)
        ->create();

    $this->actingAs($customer)
        ->post("/reservations/{$booking->id}/avis", [
            'rating' => 5,
            'comment' => 'Cet avis ne devrait jamais être enregistré.',
        ])
        ->assertForbidden();

    expect(ProviderReview::count())->toBe(0);
});

test('une réservation terminée mais dont le créneau est futur est refusée', function () {
    $customer = reviewTestCustomer();
    [, $profile] = reviewTestProfessional();

    $booking = reviewTestCompletedBooking($customer, $profile);
    $booking->forceFill(['starts_at' => now()->addDay()])->save();

    $this->actingAs($customer)
        ->post("/reservations/{$booking->id}/avis", [
            'rating' => 4,
            'comment' => 'Le créneau annoncé ne peut pas encore être évalué.',
        ])
        ->assertForbidden();

    expect(ProviderReview::count())->toBe(0);
});

test('un client ne peut pas évaluer la prestation d’un autre client', function () {
    $customer = reviewTestCustomer();
    $other = reviewTestCustomer();
    [, $profile] = reviewTestProfessional();

    $booking = reviewTestCompletedBooking($customer, $profile);

    $this->actingAs($other)
        ->post("/reservations/{$booking->id}/avis", [
            'rating' => 1,
            'comment' => 'Avis déposé par un compte qui n’est pas le client.',
        ])
        ->assertForbidden();

    expect(ProviderReview::count())->toBe(0);
});

test('un même client ne peut déposer qu’un seul avis par réservation', function () {
    $customer = reviewTestCustomer();
    [, $profile] = reviewTestProfessional();

    $booking = reviewTestCompletedBooking($customer, $profile);

    $this->actingAs($customer)->post("/reservations/{$booking->id}/avis", [
        'rating' => 5,
        'comment' => 'Premier avis valide sur cette prestation terminée.',
    ])->assertSessionHasNoErrors();

    $this->actingAs($customer)->post("/reservations/{$booking->id}/avis", [
        'rating' => 1,
        'comment' => 'Deuxième avis qui doit être refusé par la règle d’unicité.',
    ])->assertForbidden();

    expect(ProviderReview::count())->toBe(1);
});

test('la note et le commentaire sont validés', function () {
    $customer = reviewTestCustomer();
    [, $profile] = reviewTestProfessional();

    $booking = reviewTestCompletedBooking($customer, $profile);

    $this->actingAs($customer)
        ->post("/reservations/{$booking->id}/avis", [
            'rating' => 6,
            'comment' => 'Court',
        ])
        ->assertSessionHasErrors(['rating', 'comment']);

    expect(ProviderReview::count())->toBe(0);
});

test('un administrateur approuve un avis et la note du profil est recalculée', function () {
    $admin = reviewTestAdmin();
    $customer = reviewTestCustomer();
    [, $profile] = reviewTestProfessional();

    $booking = reviewTestCompletedBooking($customer, $profile);
    $review = reviewTestReview($booking, 4);

    $this->actingAs($admin)
        ->post("/admin/avis/{$review->id}/moderation", ['decision' => 'approve'])
        ->assertSessionHasNoErrors();

    $review->refresh();
    $profile->refresh();

    expect($review->status)->toBe(ReviewStatus::Approved)
        ->and($review->published_at)->not->toBeNull()
        ->and($profile->rating_count)->toBe(1)
        ->and((float) $profile->rating_average)->toBe(4.0);
});

test('un administrateur refuse un avis et celui-ci reste privé', function () {
    $admin = reviewTestAdmin();
    $customer = reviewTestCustomer();
    [, $profile] = reviewTestProfessional();

    $booking = reviewTestCompletedBooking($customer, $profile);
    $review = reviewTestReview($booking, 2);

    $this->actingAs($admin)
        ->post("/admin/avis/{$review->id}/moderation", [
            'decision' => 'reject',
            'reason' => 'Contenu inapproprié.',
        ])
        ->assertSessionHasNoErrors();

    $review->refresh();
    $profile->refresh();

    expect($review->status)->toBe(ReviewStatus::Rejected)
        ->and($review->published_at)->toBeNull()
        ->and($profile->rating_count)->toBe(0)
        ->and((float) $profile->rating_average)->toBe(0.0);
});

test('seuls les avis approuvés apparaissent sur la fiche publique', function () {
    $customer = reviewTestCustomer();
    [, $profile] = reviewTestProfessional();

    $approved = reviewTestReview(reviewTestCompletedBooking($customer, $profile), 5, ReviewStatus::Approved);
    reviewTestReview(reviewTestCompletedBooking($customer, $profile), 1, ReviewStatus::Pending);

    $this->get(route('professionals.show', $profile->slug))
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/Professional')
            ->has('reviews', 1)
            ->where('reviews.0.id', $approved->id)
            ->where('reviews.0.rating', 5)
        );
});

test('le professionnel répond une seule fois à un avis publié', function () {
    [$professional, $profile] = reviewTestProfessional();
    $customer = reviewTestCustomer();

    $review = reviewTestReview(reviewTestCompletedBooking($customer, $profile), 5, ReviewStatus::Approved);

    $this->actingAs($professional)
        ->post("/pro/avis/{$review->id}/reponse", [
            'reply' => 'Merci pour votre confiance et à très bientôt.',
        ])
        ->assertSessionHasNoErrors();

    $review->refresh();

    expect($review->professional_reply)->toBe('Merci pour votre confiance et à très bientôt.')
        ->and($review->replied_at)->not->toBeNull();

    $this->actingAs($professional)
        ->post("/pro/avis/{$review->id}/reponse", [
            'reply' => 'Une deuxième réponse qui doit être refusée.',
        ])
        ->assertForbidden();
});

test('un professionnel ne peut pas répondre à un avis en attente', function () {
    [$professional, $profile] = reviewTestProfessional();
    $customer = reviewTestCustomer();

    $review = reviewTestReview(reviewTestCompletedBooking($customer, $profile), 4);

    $this->actingAs($professional)
        ->post("/pro/avis/{$review->id}/reponse", [
            'reply' => 'Cette réponse ne doit pas être publiée avant modération.',
        ])
        ->assertForbidden();

    expect($review->refresh()->professional_reply)->toBeNull();
});

test('un autre professionnel ne peut pas répondre à un avis', function () {
    [, $profile] = reviewTestProfessional();
    [$otherProfessional] = reviewTestProfessional();
    $customer = reviewTestCustomer();

    $review = reviewTestReview(reviewTestCompletedBooking($customer, $profile), 4, ReviewStatus::Approved);

    $this->actingAs($otherProfessional)
        ->post("/pro/avis/{$review->id}/reponse", [
            'reply' => 'Réponse provenant d’un profil qui n’est pas concerné.',
        ])
        ->assertForbidden();

    expect($review->refresh()->professional_reply)->toBeNull();
});

test('un client ne peut pas modérer les avis', function () {
    $customer = reviewTestCustomer();
    [, $profile] = reviewTestProfessional();

    $review = reviewTestReview(reviewTestCompletedBooking($customer, $profile), 3);

    $this->actingAs($customer)
        ->get('/admin/avis')
        ->assertForbidden();

    $this->actingAs($customer)
        ->post("/admin/avis/{$review->id}/moderation", ['decision' => 'approve'])
        ->assertForbidden();

    expect($review->refresh()->status)->toBe(ReviewStatus::Pending);
});

test('le client ne consulte que ses propres avis', function () {
    $customer = reviewTestCustomer();
    $other = reviewTestCustomer();
    [, $profile] = reviewTestProfessional();

    $own = reviewTestReview(reviewTestCompletedBooking($customer, $profile));
    reviewTestReview(reviewTestCompletedBooking($other, $profile));

    $this->actingAs($customer)
        ->get('/avis')
        ->assertInertia(fn (Assert $page) => $page
            ->component('requests/Reviews')
            ->has('reviews.data', 1)
            ->where('reviews.data.0.id', $own->id)
        );
});

test('le professionnel ne consulte que les avis de ses profils', function () {
    [$professional, $profile] = reviewTestProfessional();
    [, $otherProfile] = reviewTestProfessional();
    $customer = reviewTestCustomer();

    $own = reviewTestReview(reviewTestCompletedBooking($customer, $profile));
    reviewTestReview(reviewTestCompletedBooking($customer, $otherProfile));

    $this->actingAs($professional)
        ->get('/pro/avis')
        ->assertInertia(fn (Assert $page) => $page
            ->component('pro/Reviews')
            ->has('reviews.data', 1)
            ->where('reviews.data.0.id', $own->id)
        );
});

test('un visiteur ne peut pas déposer d’avis', function () {
    $customer = reviewTestCustomer();
    [, $profile] = reviewTestProfessional();

    $booking = reviewTestCompletedBooking($customer, $profile);

    $this->post("/reservations/{$booking->id}/avis", [
        'rating' => 5,
        'comment' => 'Un visiteur ne doit jamais pouvoir déposer un avis.',
    ])->assertRedirect(route('login'));

    expect(ProviderReview::count())->toBe(0);
});
