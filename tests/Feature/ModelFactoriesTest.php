<?php

use App\Models\Booking;
use App\Models\Category;
use App\Models\Professional;
use App\Models\Quote;
use App\Models\Review;
use App\Models\Service;
use App\Models\Subcategory;
use App\Models\User;

test('les factories créent des enregistrements cohérents', function () {
    $category = Category::factory()->create();
    $subcategory = Subcategory::factory()->for($category)->create();
    $professional = Professional::factory()
        ->active()
        ->verified()
        ->forCategory($category)
        ->create(['subcategory_id' => $subcategory->id]);

    $service = Service::factory()
        ->for($professional)
        ->fixedPrice(25000)
        ->create();

    $customer = User::factory()->create();

    $quote = Quote::factory()
        ->for($professional)
        ->for($customer, 'customer')
        ->create();

    $booking = Booking::factory()
        ->for($professional)
        ->for($customer, 'customer')
        ->create();

    $review = Review::factory()->for($booking)->create();

    expect($category->exists)->toBeTrue()
        ->and($subcategory->category_id)->toBe($category->id)
        ->and($professional->category_id)->toBe($category->id)
        ->and($professional->status)->toBe(Professional::STATUS_ACTIVE)
        ->and($professional->is_verified)->toBeTrue()
        ->and($service->professional_id)->toBe($professional->id)
        ->and($service->formatted_price)->toContain('FCFA')
        ->and($quote->reference)->toStartWith('DV-')
        ->and($quote->customer_id)->toBe($customer->id)
        ->and($booking->reference)->toStartWith('RV-')
        ->and($review->professional_id)->toBe($professional->id)
        ->and($review->customer_id)->toBe($customer->id)
        ->and($review->booking_id)->toBe($booking->id);
});

test('les états des factories permettent de simuler les cas métier', function () {
    $inactiveCategory = Category::factory()->inactive()->create();
    $suspendedProfessional = Professional::factory()->suspended()->create();
    $inactiveService = Service::factory()->inactive()->create();
    $quotedQuote = Quote::factory()->quoted(75000)->create();
    $completedBooking = Booking::factory()->completed()->create();
    $approvedReview = Review::factory()->approved()->replied()->create();

    expect($inactiveCategory->is_active)->toBeFalse()
        ->and($suspendedProfessional->status)->toBe(Professional::STATUS_SUSPENDED)
        ->and($inactiveService->is_active)->toBeFalse()
        ->and($quotedQuote->status)->toBe(Quote::STATUS_QUOTED)
        ->and($quotedQuote->quoted_amount)->toBe('75000')
        ->and($completedBooking->status)->toBe(Booking::STATUS_COMPLETED)
        ->and($completedBooking->completed_at)->not->toBeNull()
        ->and($approvedReview->is_approved)->toBeTrue()
        ->and($approvedReview->replied_at)->not->toBeNull();
});
