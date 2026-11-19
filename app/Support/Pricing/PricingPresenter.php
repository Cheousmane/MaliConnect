<?php

namespace App\Support\Pricing;

use App\Models\PricingItem;
use App\Models\PricingSheet;
use Illuminate\Support\Facades\Storage;

/**
 * Transforme les fiches tarifaires en données pour Inertia.
 *
 * Deux représentations coexistent : la vue publique, limitée aux prestations
 * disponibles et aux fiches publiées, et la vue de gestion qui expose les
 * valeurs brutes nécessaires aux formulaires.
 */
final class PricingPresenter
{
    /**
     * Représentation publique d'une fiche.
     *
     * @return array<string, mixed>
     */
    public static function publicSheet(PricingSheet $sheet): array
    {
        $profile = $sheet->providerProfile;

        return [
            'id' => $sheet->id,
            'name' => $sheet->name,
            'description' => $sheet->description,
            'currency' => $sheet->currency,
            'currency_label' => self::currencyLabel($sheet->currency),
            'general_conditions' => $sheet->general_conditions,
            'valid_until' => $sheet->valid_until?->toDateString(),
            'updated_at' => $sheet->updated_at?->toIso8601String(),
            'published_at' => $sheet->published_at?->toIso8601String(),
            'category' => $sheet->category?->only(['id', 'name']),
            'public_url' => route('public.pricing-sheets.show', $sheet->public_token),
            'pdf_url' => route('public.pricing-sheets.pdf', $sheet->public_token),
            'profile' => $profile === null ? null : [
                'id' => $profile->id,
                'business_name' => $profile->business_name,
                'slug' => $profile->slug,
                'phone' => $profile->phone,
                'whatsapp' => $profile->whatsapp,
                'email' => $profile->email,
                'address' => $profile->address,
                'city' => $profile->city,
                'commune' => $profile->commune,
                'logo_url' => $profile->logo_path
                    ? Storage::disk('public')->url($profile->logo_path)
                    : null,
            ],
            'items' => $sheet->items
                ->where('is_available', true)
                ->values()
                ->map(fn (PricingItem $item) => self::publicItem($item, $profile?->slug))
                ->all(),
        ];
    }

    /**
     * Représentation d'une fiche dans l'espace professionnel.
     *
     * @return array<string, mixed>
     */
    public static function manageSheet(PricingSheet $sheet): array
    {
        $sheet->loadMissing(['providerProfile', 'category', 'items.service', 'items.category']);

        return [
            'id' => $sheet->id,
            'provider_profile_id' => $sheet->provider_profile_id,
            'name' => $sheet->name,
            'description' => $sheet->description,
            'currency' => $sheet->currency,
            'currency_label' => self::currencyLabel($sheet->currency),
            'general_conditions' => $sheet->general_conditions,
            'status' => $sheet->status->value,
            'status_label' => $sheet->status->label(),
            'status_tone' => $sheet->status->tone(),
            'valid_until' => $sheet->valid_until?->toDateString(),
            'published_at' => $sheet->published_at?->toIso8601String(),
            'archived_at' => $sheet->archived_at?->toIso8601String(),
            'created_at' => $sheet->created_at?->toIso8601String(),
            'updated_at' => $sheet->updated_at?->toIso8601String(),
            'is_expired' => $sheet->isExpired(),
            'category' => $sheet->category?->only(['id', 'name']),
            'profile' => $sheet->providerProfile?->only(['id', 'business_name', 'slug']),
            'public_url' => route('public.pricing-sheets.show', $sheet->public_token),
            'pdf_url' => route('public.pricing-sheets.pdf', $sheet->public_token),
            'items' => $sheet->items
                ->map(fn (PricingItem $item) => self::manageItem($item))
                ->values()
                ->all(),
        ];
    }

    /**
     * Représentation publique d'une prestation.
     *
     * @return array<string, mixed>
     */
    public static function publicItem(PricingItem $item, ?string $profileSlug = null): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'category' => $item->category?->only(['id', 'name']),
            'service_id' => $item->service_id,
            'service_name' => $item->service?->name,
            'pricing_type' => $item->pricing_type->value,
            'pricing_type_label' => $item->pricing_type->label(),
            'price' => $item->price,
            'price_min' => $item->price_min,
            'price_max' => $item->price_max,
            'formatted_price' => $item->formatted_price,
            'billing_unit' => $item->billing_unit,
            'conditions' => $item->conditions,
            'estimated_duration_minutes' => $item->estimated_duration_minutes,
            'formatted_duration' => $item->formatted_duration,
            'is_available' => $item->is_available,
            'requires_diagnostic' => $item->requires_diagnostic,
            'is_quote' => $item->isQuote(),
            'has_price' => $item->hasPrice(),
            'quote_url' => $profileSlug === null
                ? null
                : route('requests.create', ['profil' => $profileSlug, 'tarif' => $item->id]),
        ];
    }

    /**
     * Représentation d'une prestation dans l'espace professionnel.
     *
     * @return array<string, mixed>
     */
    public static function manageItem(PricingItem $item): array
    {
        return [
            'id' => $item->id,
            'service_id' => $item->service_id,
            'service_name' => $item->service?->name,
            'category_id' => $item->category_id,
            'category_name' => $item->category?->name,
            'name' => $item->name,
            'description' => $item->description,
            'pricing_type' => $item->pricing_type->value,
            'pricing_type_label' => $item->pricing_type->label(),
            'price' => $item->price,
            'price_min' => $item->price_min,
            'price_max' => $item->price_max,
            'formatted_price' => $item->formatted_price,
            'billing_unit' => $item->billing_unit,
            'conditions' => $item->conditions,
            'estimated_duration_minutes' => $item->estimated_duration_minutes,
            'formatted_duration' => $item->formatted_duration,
            'is_available' => $item->is_available,
            'requires_diagnostic' => $item->requires_diagnostic,
            'sort_order' => $item->sort_order,
        ];
    }

    /**
     * Symbole lisible d'une devise.
     */
    public static function currencyLabel(string $currency): string
    {
        return $currency === 'XOF' ? 'FCFA' : $currency;
    }
}
