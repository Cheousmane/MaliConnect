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
    Route::post('demandes', [ServiceRequestController::class, 'store'])->middleware('throttle:10,1')->name('requests.store');
    Route::get('demandes/{serviceRequest}', [ServiceRequestController::class, 'show'])->name('requests.show');
    Route::get('demandes/{serviceRequest}/pieces-jointes/{attachment}', [ServiceRequestController::class, 'downloadAttachment'])
        ->middleware('throttle:30,1')
        ->name('requests.attachments.download');
    Route::post('demandes/{serviceRequest}/messages', [ServiceRequestController::class, 'storeMessage'])->middleware('throttle:20,1')->name('requests.messages.store');
    Route::post('demandes/{serviceRequest}/annuler', [ServiceRequestController::class, 'cancel'])->middleware('throttle:20,1')->name('requests.cancel');
    Route::post('demandes/{serviceRequest}/devis/{quote}/accepter', [ServiceRequestController::class, 'acceptQuote'])
        ->middleware('throttle:20,1')
        ->name('requests.quotes.accept');
    Route::post('demandes/{serviceRequest}/devis/{quote}/refuser', [ServiceRequestController::class, 'rejectQuote'])
        ->middleware('throttle:20,1')
        ->name('requests.quotes.reject');

    Route::get('reservations', [BookingController::class, 'index'])->name('bookings.index');
    Route::post('reservations/{booking}/annuler', [BookingController::class, 'cancel'])->middleware('throttle:20,1')->name('bookings.cancel');
});
