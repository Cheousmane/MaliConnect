<?php

namespace App\Support\Catalog;

use App\Models\ProviderProfile;
use App\Models\Service;
use Illuminate\Support\Facades\Storage;

/**
 * Transforme un profil professionnel en données exploitables par l'interface.
 *
 * Centraliser la présentation évite de dupliquer la structure des props et
 * garantit que les pages publiques exposent les mêmes informations.
 */
final class ProfilePresenter
{
    /**
     * Résumé d'un profil pour les listes et les résultats de recherche.
     *
     * @return array<string, mixed>
     */
    public static function summary(ProviderProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'business_name' => $profile->business_name,
            'slug' => $profile->slug,
            'description' => $profile->description,
            'city' => $profile->city,
            'commune' => $profile->commune,
            'quartier' => $profile->quartier,
            'is_available' => $profile->is_available,
            'rating_average' => (float) $profile->rating_average,
            'rating_count' => $profile->rating_count,
            'category' => $profile->category?->only(['id', 'name', 'slug']),
            'subcategory' => $profile->subcategory?->only(['id', 'name', 'slug']),
            'business' => $profile->business?->only(['id', 'name']),
            'services_count' => $profile->services_count,
            'media_count' => $profile->media_count,
            'logo_url' => $profile->logo_path
                ? Storage::disk('public')->url($profile->logo_path)
                : null,
        ];
    }

    /**
     * Fiche complète d'un profil public.
     *
     * @return array<string, mixed>
     */
    public static function detail(ProviderProfile $profile): array
    {
        return [
            ...self::summary($profile),
            'phone' => $profile->phone,
            'whatsapp' => $profile->whatsapp,
            'email' => $profile->email,
            'address' => $profile->address,
            'latitude' => $profile->latitude,
            'longitude' => $profile->longitude,
            'years_experience' => $profile->years_experience,
            'response_time_hours' => $profile->response_time_hours,
            'verified_at' => $profile->verified_at?->toIso8601String(),
            'services' => $profile->services->map(fn (Service $service) => self::service($service))->values()->all(),
            'areas' => $profile->serviceAreas->map(fn ($area) => [
                'city' => $area->city,
                'commune' => $area->commune,
                'quartier' => $area->quartier,
                'is_primary' => $area->is_primary,
            ])->values()->all(),
            'hours' => $profile->hours->map(fn ($hour) => [
                'day_of_week' => $hour->day_of_week,
                'opens_at' => $hour->opens_at,
                'closes_at' => $hour->closes_at,
                'is_closed' => $hour->is_closed,
            ])->values()->all(),
            'media' => $profile->media->map(fn ($media) => [
                'id' => $media->id,
                'type_label' => $media->type->label(),
                'url' => $media->url,
                'caption' => $media->caption,
            ])->values()->all(),
        ];
    }

    /**
     * Résumé d'un service proposé.
     *
     * @return array<string, mixed>
     */
    public static function service(Service $service): array
    {
        return [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'price_type' => $service->price_type,
            'price_min' => $service->price_min,
            'price_max' => $service->price_max,
            'duration_minutes' => $service->duration_minutes,
            'is_active' => $service->is_active,
            'formatted_price' => $service->formatted_price,
        ];
    }
}
