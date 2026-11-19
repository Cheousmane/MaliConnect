<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PricingSheet;
use App\Support\Pricing\PricingPresenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Consultation publique d'une fiche tarifaire partagée.
 *
 * L'accès repose sur le jeton public de la fiche. Une fiche brouillon,
 * archivée, expirée ou rattachée à un profil non public renvoie une 404.
 */
class PricingSheetController extends Controller
{
    /**
     * Affiche la fiche tarifaire publique.
     */
    public function show(PricingSheet $pricingSheet): Response
    {
        $this->ensurePubliclyVisible($pricingSheet);

        $pricingSheet->load([
            'providerProfile',
            'category',
            'items' => fn ($query) => $query->where('is_available', true),
            'items.service',
            'items.category',
        ]);

        return Inertia::render('public/PricingSheet', [
            'sheet' => PricingPresenter::publicSheet($pricingSheet),
        ]);
    }

    /**
     * Télécharge la fiche tarifaire en PDF.
     */
    public function pdf(PricingSheet $pricingSheet): HttpResponse
    {
        $this->ensurePubliclyVisible($pricingSheet);

        $pricingSheet->load([
            'providerProfile',
            'category',
            'items' => fn ($query) => $query->where('is_available', true),
            'items.service',
            'items.category',
        ]);

        $profile = $pricingSheet->providerProfile;

        $pdf = Pdf::loadView('pdf.pricing-sheet', [
            'sheet' => $pricingSheet,
            'profile' => $profile,
            'logo' => $this->logoDataUri($profile?->logo_path),
            'currencyLabel' => PricingPresenter::currencyLabel($pricingSheet->currency),
            'generatedAt' => now(),
        ])->setPaper('a4');

        $filename = Str::slug(($profile?->business_name ?? 'maliconnect').'-'.$pricingSheet->name).'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Vérifie que la fiche peut être exposée publiquement.
     */
    private function ensurePubliclyVisible(PricingSheet $pricingSheet): void
    {
        $pricingSheet->loadMissing('providerProfile');

        abort_unless($pricingSheet->isPubliclyVisible(), 404);
    }

    /**
     * Encode le logo en data URI pour une impression PDF autonome.
     */
    private function logoDataUri(?string $path): ?string
    {
        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $contents = Storage::disk('public')->get($path);
        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }
}
