<?php

use App\Http\Controllers\Favorites\FavoriteController;
use App\Http\Controllers\Notifications\NotificationController;
use App\Http\Controllers\Requests\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes du compte : favoris, notifications et avis client
|--------------------------------------------------------------------------
|
| Ces fonctionnalités sont accessibles à tout compte authentifié, vérifié
| et actif. Les requêtes restent systématiquement limitées au compte connecté.
|
*/

Route::middleware(['auth', 'verified', 'active', 'throttle:60,1'])->group(function () {
    // Favoris
    Route::get('favoris', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('favoris/{profile}', [FavoriteController::class, 'store'])->name('favorites.store');
    Route::delete('favoris/{profile}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');

    // Centre de notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/tout-lire', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('notifications/{notification}/lire', [NotificationController::class, 'read'])->name('notifications.read');
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Avis client
    Route::get('avis', [ReviewController::class, 'index'])->name('reviews.index');
    Route::get('reservations/{booking}/avis', [ReviewController::class, 'create'])->name('reviews.create');
    Route::post('reservations/{booking}/avis', [ReviewController::class, 'store'])->name('reviews.store');
});
