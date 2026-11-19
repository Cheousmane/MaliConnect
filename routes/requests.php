<?php

use App\Http\Controllers\Requests\BookingController;
use App\Http\Controllers\Requests\ServiceRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes des demandes de devis et réservations (espace client)
|--------------------------------------------------------------------------
|
| Chaque action vérifie les droits du client sur la demande concernée :
| un client n'accède qu'à ses propres demandes et réservations.
|
*/

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('demandes', [ServiceRequestController::class, 'index'])->name('requests.index');
    Route::get('demandes/nouveau', [ServiceRequestController::class, 'create'])->name('requests.create');
    Route::post('demandes', [ServiceRequestController::class, 'store'])->name('requests.store');
    Route::get('demandes/{serviceRequest}', [ServiceRequestController::class, 'show'])->name('requests.show');
    Route::get('demandes/{serviceRequest}/pieces-jointes/{attachment}', [ServiceRequestController::class, 'downloadAttachment'])
        ->name('requests.attachments.download');
    Route::post('demandes/{serviceRequest}/messages', [ServiceRequestController::class, 'storeMessage'])->name('requests.messages.store');
    Route::post('demandes/{serviceRequest}/annuler', [ServiceRequestController::class, 'cancel'])->name('requests.cancel');
    Route::post('demandes/{serviceRequest}/devis/{quote}/accepter', [ServiceRequestController::class, 'acceptQuote'])
        ->name('requests.quotes.accept');
    Route::post('demandes/{serviceRequest}/devis/{quote}/refuser', [ServiceRequestController::class, 'rejectQuote'])
        ->name('requests.quotes.reject');

    Route::get('reservations', [BookingController::class, 'index'])->name('bookings.index');
    Route::post('reservations/{booking}/annuler', [BookingController::class, 'cancel'])->name('bookings.cancel');
});
