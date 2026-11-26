<?php

use App\Http\Controllers\Pro\BookingController;
use App\Http\Controllers\Pro\BusinessController;
use App\Http\Controllers\Pro\BusinessMemberController;
use App\Http\Controllers\Pro\PricingSheetController;
use App\Http\Controllers\Pro\ProviderProfileController;
use App\Http\Controllers\Pro\ReviewController;
use App\Http\Controllers\Pro\ServiceRequestController;
use App\Http\Controllers\Pro\SubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes de l'espace professionnel
|--------------------------------------------------------------------------
|
| Ces routes sont réservées aux comptes vérifiés et actifs. Les droits
| précis sont contrôlés par les policies ProviderProfilePolicy et
| BusinessPolicy.
|
*/

Route::middleware(['auth', 'verified', 'active', 'throttle:120,1'])
    ->prefix('pro')
    ->name('pro.')
    ->group(function () {
        // Profils professionnels
        Route::get('profiles', [ProviderProfileController::class, 'index'])->name('profiles.index');
        Route::get('profiles/create', [ProviderProfileController::class, 'create'])->name('profiles.create');
        Route::post('profiles', [ProviderProfileController::class, 'store'])->name('profiles.store');
        Route::get('profiles/{profile}', [ProviderProfileController::class, 'show'])->name('profiles.show');
        Route::get('profiles/{profile}/edit', [ProviderProfileController::class, 'edit'])->name('profiles.edit');
        Route::patch('profiles/{profile}', [ProviderProfileController::class, 'update'])->name('profiles.update');
        Route::post('profiles/{profile}/submit', [ProviderProfileController::class, 'submit'])->name('profiles.submit');
        Route::post('profiles/{profile}/deactivate', [ProviderProfileController::class, 'deactivate'])->name('profiles.deactivate');
        Route::post('profiles/{profile}/media', [ProviderProfileController::class, 'storeMedia'])->name('profiles.media.store');
        Route::delete('profiles/{profile}/media/{media}', [ProviderProfileController::class, 'destroyMedia'])->name('profiles.media.destroy');
        Route::put('profiles/{profile}/areas', [ProviderProfileController::class, 'updateAreas'])->name('profiles.areas.update');
        Route::put('profiles/{profile}/schedule', [ProviderProfileController::class, 'updateSchedule'])->name('profiles.schedule.update');
        Route::post('profiles/{profile}/services', [ProviderProfileController::class, 'storeService'])->name('profiles.services.store');
        Route::patch('profiles/{profile}/services/{service}', [ProviderProfileController::class, 'updateService'])->name('profiles.services.update');
        Route::delete('profiles/{profile}/services/{service}', [ProviderProfileController::class, 'destroyService'])->name('profiles.services.destroy');

        // Abonnement et formules
        Route::get('abonnement', [SubscriptionController::class, 'index'])->name('subscription.index');
        Route::get('abonnement/offres', [SubscriptionController::class, 'plans'])->name('subscription.plans');
        Route::post('abonnement/souscrire', [SubscriptionController::class, 'subscribe'])->name('subscription.subscribe');
        Route::post('abonnement/{subscription}/retour-free', [SubscriptionController::class, 'requestFree'])->name('subscription.free');
        Route::post('abonnement/{subscription}/resilier', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
        Route::get('abonnement/paiements/{payment}/preuves/{proof}', [SubscriptionController::class, 'downloadProof'])->name('subscription.proofs.download');

        // Fiches tarifaires
        Route::get('tarifs', [PricingSheetController::class, 'index'])->name('pricing-sheets.index');
        Route::get('tarifs/nouveau', [PricingSheetController::class, 'create'])->name('pricing-sheets.create');
        Route::post('tarifs', [PricingSheetController::class, 'store'])->name('pricing-sheets.store');
        Route::get('tarifs/{sheet}', [PricingSheetController::class, 'show'])->name('pricing-sheets.show');
        Route::get('tarifs/{sheet}/apercu', [PricingSheetController::class, 'preview'])->name('pricing-sheets.preview');
        Route::patch('tarifs/{sheet}', [PricingSheetController::class, 'update'])->name('pricing-sheets.update');
        Route::delete('tarifs/{sheet}', [PricingSheetController::class, 'destroy'])->name('pricing-sheets.destroy');
        Route::post('tarifs/{sheet}/dupliquer', [PricingSheetController::class, 'duplicate'])->name('pricing-sheets.duplicate');
        Route::patch('tarifs/{sheet}/statut', [PricingSheetController::class, 'updateStatus'])->name('pricing-sheets.status');
        Route::post('tarifs/{sheet}/prestations', [PricingSheetController::class, 'storeItem'])->name('pricing-sheets.items.store');
        Route::post('tarifs/{sheet}/prestations/reorganiser', [PricingSheetController::class, 'reorderItems'])->name('pricing-sheets.items.reorder');
        Route::patch('tarifs/{sheet}/prestations/{item}', [PricingSheetController::class, 'updateItem'])->name('pricing-sheets.items.update');
        Route::delete('tarifs/{sheet}/prestations/{item}', [PricingSheetController::class, 'destroyItem'])->name('pricing-sheets.items.destroy');

        // Entreprises et collaborateurs
        Route::get('businesses', [BusinessController::class, 'index'])->name('businesses.index');
        Route::get('businesses/create', [BusinessController::class, 'create'])->name('businesses.create');
        Route::post('businesses', [BusinessController::class, 'store'])->name('businesses.store');
        Route::get('businesses/{business}', [BusinessController::class, 'show'])->name('businesses.show');
        Route::patch('businesses/{business}', [BusinessController::class, 'update'])->name('businesses.update');
        Route::post('businesses/{business}/members', [BusinessMemberController::class, 'store'])->name('businesses.members.store');
        Route::get('businesses/{business}/members/search', [BusinessMemberController::class, 'search'])->name('businesses.members.search');
        Route::patch('businesses/{business}/members/{member}', [BusinessMemberController::class, 'update'])->name('businesses.members.update');
        Route::delete('businesses/{business}/members/{member}', [BusinessMemberController::class, 'destroy'])->name('businesses.members.destroy');

        // Demandes de devis reçues
        Route::get('demandes', [ServiceRequestController::class, 'index'])->name('requests.index');
        Route::get('demandes/{serviceRequest}', [ServiceRequestController::class, 'show'])->name('requests.show');
        Route::post('demandes/{serviceRequest}/statut', [ServiceRequestController::class, 'updateStatus'])->name('requests.status.update');
        Route::post('demandes/{serviceRequest}/messages', [ServiceRequestController::class, 'storeMessage'])->name('requests.messages.store');
        Route::post('demandes/{serviceRequest}/devis', [ServiceRequestController::class, 'storeQuote'])->name('requests.quotes.store');

        // Réservations
        Route::get('reservations', [BookingController::class, 'index'])->name('bookings.index');
        Route::post('reservations/{booking}/statut', [BookingController::class, 'updateStatus'])->name('bookings.status.update');

        // Avis reçus
        Route::get('avis', [ReviewController::class, 'index'])->name('reviews.index');
        Route::post('avis/{review}/reponse', [ReviewController::class, 'respond'])->name('reviews.respond');
    });
