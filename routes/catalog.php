<?php

use App\Http\Controllers\Public\CatalogController;
use App\Http\Controllers\Public\PricingSheetController;
use App\Http\Controllers\Public\ProfessionalController;
use App\Http\Controllers\Public\ReportController;
use App\Http\Controllers\Public\SearchController;
use App\Http\Controllers\Public\ServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes du catalogue public
|--------------------------------------------------------------------------
|
| Le catalogue n'expose que des profils vérifiés et actifs : la recherche et
| les fiches publiques partagent le même service de recherche.
|
*/

Route::get('categories', [CatalogController::class, 'index'])->name('categories.index');
Route::get('categories/{category:slug}', [CatalogController::class, 'show'])->name('categories.show');

Route::get('recherche', [SearchController::class, 'index'])->name('search');
Route::get('recherche/suggestions', [SearchController::class, 'suggestions'])
    ->middleware('throttle:60,1')
    ->name('search.suggestions');

Route::get('professionnels/{profile:slug}', [ProfessionalController::class, 'show'])->name('professionals.show');

// Fiche tarifaire publique partageable par lien ou WhatsApp.
Route::get('tarifs/{pricingSheet:public_token}', [PricingSheetController::class, 'show'])
    ->name('public.pricing-sheets.show');
Route::get('tarifs/{pricingSheet:public_token}/pdf', [PricingSheetController::class, 'pdf'])
    ->name('public.pricing-sheets.pdf');

// Signalement d'une fiche publique par un compte authentifié et habilité.
Route::post('professionnels/{profile:slug}/signaler', [ReportController::class, 'store'])
    ->middleware(['auth', 'verified', 'active', 'permission:reports.create', 'throttle:10,1'])
    ->name('professionals.report');

Route::get('services/{service}', [ServiceController::class, 'show'])->name('services.show');
