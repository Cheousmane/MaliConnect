<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\BusinessController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\ProfessionalController;
use App\Http\Controllers\Admin\ProviderProfileController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\ServiceRequestController;
use App\Http\Controllers\Admin\SubcategoryController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes de l'administration
|--------------------------------------------------------------------------
|
| L'accès est réservé aux rôles d'administration vérifiés, dont le compte
| doit être actif. Chaque module applique en plus sa permission dédiée et
| ses policies : aucune opération sensible n'est validée côté client.
|
*/

Route::middleware(['auth', 'verified', 'active', 'role:super-admin|admin', 'throttle:120,1'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Tableau de bord analytique.
        Route::get('/', [DashboardController::class, 'index'])
            ->middleware('permission:statistics.view')
            ->name('dashboard');

        // Comptes utilisateurs.
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::patch('users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status.update');
        Route::patch('users/{user}/roles', [UserController::class, 'updateRoles'])->name('users.roles.update');

        // Entreprises.
        Route::get('businesses', [BusinessController::class, 'index'])
            ->middleware('permission:businesses.view.all')
            ->name('businesses.index');
        Route::get('businesses/{business}', [BusinessController::class, 'show'])
            ->middleware('permission:businesses.view.all')
            ->name('businesses.show');
        Route::patch('businesses/{business}/status', [BusinessController::class, 'updateStatus'])
            ->name('businesses.status.update');

        // Professionnels (supervision de tous les profils).
        Route::get('professionals', [ProfessionalController::class, 'index'])
            ->middleware('permission:provider_profiles.view.all')
            ->name('professionals.index');
        Route::patch('professionals/{profile}/activation', [ProfessionalController::class, 'updateActivation'])
            ->name('professionals.activation.update');

        // Vérification administrative des profils professionnels.
        Route::get('provider-profiles', [ProviderProfileController::class, 'index'])
            ->middleware('permission:provider_profiles.view.all')
            ->name('provider-profiles.index');
        Route::get('provider-profiles/{profile}', [ProviderProfileController::class, 'show'])
            ->middleware('permission:provider_profiles.view.all')
            ->name('provider-profiles.show');
        Route::post('provider-profiles/{profile}/review', [ProviderProfileController::class, 'review'])
            ->name('provider-profiles.review');

        // Catalogue : catégories, sous-catégories et services.
        Route::get('categories', [CategoryController::class, 'index'])
            ->middleware('permission:categories.manage')
            ->name('categories.index');
        Route::post('categories', [CategoryController::class, 'store'])
            ->middleware('permission:categories.manage')
            ->name('categories.store');
        Route::patch('categories/{category}', [CategoryController::class, 'update'])
            ->middleware('permission:categories.manage')
            ->name('categories.update');
        Route::patch('categories/{category}/activation', [CategoryController::class, 'updateActivation'])
            ->middleware('permission:categories.manage')
            ->name('categories.activation.update');

        Route::get('sous-categories', [SubcategoryController::class, 'index'])
            ->middleware('permission:categories.manage')
            ->name('subcategories.index');
        Route::post('sous-categories', [SubcategoryController::class, 'store'])
            ->middleware('permission:categories.manage')
            ->name('subcategories.store');
        Route::patch('sous-categories/{subcategory}', [SubcategoryController::class, 'update'])
            ->middleware('permission:categories.manage')
            ->name('subcategories.update');
        Route::patch('sous-categories/{subcategory}/activation', [SubcategoryController::class, 'updateActivation'])
            ->middleware('permission:categories.manage')
            ->name('subcategories.activation.update');

        Route::get('services', [ServiceController::class, 'index'])
            ->middleware('permission:services.manage')
            ->name('services.index');
        Route::patch('services/{service}/activation', [ServiceController::class, 'updateActivation'])
            ->middleware('permission:services.manage')
            ->name('services.activation.update');

        // Demandes de devis.
        Route::get('demandes', [ServiceRequestController::class, 'index'])
            ->middleware('permission:service_requests.manage')
            ->name('service-requests.index');
        Route::get('demandes/{serviceRequest}', [ServiceRequestController::class, 'show'])
            ->middleware('permission:service_requests.manage')
            ->name('service-requests.show');
        Route::patch('demandes/{serviceRequest}/statut', [ServiceRequestController::class, 'updateStatus'])
            ->middleware('permission:service_requests.manage')
            ->name('service-requests.status.update');

        // Réservations.
        Route::get('reservations', [BookingController::class, 'index'])
            ->middleware('permission:bookings.manage')
            ->name('bookings.index');
        Route::get('reservations/{booking}', [BookingController::class, 'show'])
            ->middleware('permission:bookings.manage')
            ->name('bookings.show');
        Route::patch('reservations/{booking}/statut', [BookingController::class, 'updateStatus'])
            ->middleware('permission:bookings.manage')
            ->name('bookings.status.update');

        // Modération des avis clients.
        Route::get('avis', [ReviewController::class, 'index'])
            ->middleware('permission:reviews.manage')
            ->name('reviews.index');
        Route::post('avis/{review}/moderation', [ReviewController::class, 'moderate'])
            ->middleware('permission:reviews.manage')
            ->name('reviews.moderate');

        // Formules commerciales (prix, limites et fonctionnalités).
        Route::get('formules', [PlanController::class, 'index'])
            ->middleware('permission:plans.view')
            ->name('plans.index');
        Route::post('formules', [PlanController::class, 'store'])
            ->middleware('permission:plans.manage')
            ->name('plans.store');
        Route::patch('formules/{plan}', [PlanController::class, 'update'])
            ->middleware('permission:plans.manage')
            ->name('plans.update');
        Route::patch('formules/{plan}/activation', [PlanController::class, 'updateActivation'])
            ->middleware('permission:plans.manage')
            ->name('plans.activation.update');

        // Abonnements.
        Route::get('abonnements', [SubscriptionController::class, 'index'])
            ->middleware('permission:subscriptions.view')
            ->name('subscriptions.index');
        Route::get('abonnements/{subscription}', [SubscriptionController::class, 'show'])
            ->middleware('permission:subscriptions.view')
            ->name('subscriptions.show');
        Route::patch('abonnements/{subscription}/suspension', [SubscriptionController::class, 'suspend'])
            ->middleware('permission:subscriptions.manage')
            ->name('subscriptions.suspend');
        Route::patch('abonnements/{subscription}/reactivation', [SubscriptionController::class, 'reactivate'])
            ->middleware('permission:subscriptions.manage')
            ->name('subscriptions.reactivate');
        Route::patch('abonnements/{subscription}/resiliation', [SubscriptionController::class, 'cancel'])
            ->middleware('permission:subscriptions.manage')
            ->name('subscriptions.cancel');
        Route::patch('abonnements/{subscription}/prolongation', [SubscriptionController::class, 'extend'])
            ->middleware('permission:subscriptions.manage')
            ->name('subscriptions.extend');

        // Paiements.
        Route::get('paiements', [PaymentController::class, 'index'])
            ->middleware('permission:payments.view')
            ->name('payments.index');
        Route::get('paiements/{payment}', [PaymentController::class, 'show'])
            ->middleware('permission:payments.view')
            ->name('payments.show');
        Route::post('paiements/{payment}/validation', [PaymentController::class, 'approve'])
            ->middleware('permission:payments.manage')
            ->name('payments.approve');
        Route::post('paiements/{payment}/refus', [PaymentController::class, 'reject'])
            ->middleware('permission:payments.manage')
            ->name('payments.reject');
        Route::get('paiements/{payment}/preuves/{proof}', [PaymentController::class, 'downloadProof'])
            ->middleware('permission:payments.view')
            ->name('payments.proofs.download');

        // Signalements.
        Route::get('signalements', [ReportController::class, 'index'])
            ->middleware('permission:reports.view')
            ->name('reports.index');
        Route::get('signalements/{report}', [ReportController::class, 'show'])
            ->middleware('permission:reports.view')
            ->name('reports.show');
        Route::patch('signalements/{report}/statut', [ReportController::class, 'updateStatus'])
            ->middleware('permission:reports.manage')
            ->name('reports.status.update');

        // Journal d'audit.
        Route::get('journal', [AuditLogController::class, 'index'])
            ->middleware('permission:audit.view')
            ->name('audit.index');
        Route::get('journal/{log}', [AuditLogController::class, 'show'])
            ->middleware('permission:audit.view')
            ->name('audit.show');

        // Alias pour compatibilité.
        Route::redirect('audit', 'journal');
    });
