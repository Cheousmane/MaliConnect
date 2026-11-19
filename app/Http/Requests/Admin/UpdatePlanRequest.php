<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Mise à jour d'une formule commerciale.
 *
 * Le code technique reste immuable : il est référencé par les abonnements et
 * les paiements historiques.
 */
class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('plans.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price_monthly' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'price_yearly' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'currency' => ['required', 'string', 'size:3'],
            'max_services' => ['required', 'integer', 'min:0', 'max:100000'],
            'max_media' => ['required', 'integer', 'min:0', 'max:100000'],
            'max_profiles' => ['required', 'integer', 'min:0', 'max:100000'],
            'max_pricing_sheets' => ['required', 'integer', 'min:0', 'max:100000'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['required', 'boolean'],
            'has_advanced_stats' => ['required', 'boolean'],
            'has_gallery' => ['required', 'boolean'],
            'has_collaborators' => ['required', 'boolean'],
            'has_priority_visibility' => ['required', 'boolean'],
            'has_advanced_quotes' => ['required', 'boolean'],
            'has_bookings' => ['required', 'boolean'],
            'has_priority_support' => ['required', 'boolean'],
            'has_highlight_options' => ['required', 'boolean'],
            'has_profile_badge' => ['required', 'boolean'],
            'has_centralized_requests' => ['required', 'boolean'],
            'has_full_pricing_sheets' => ['required', 'boolean'],
            'has_simple_pricing_sheets' => ['required', 'boolean'],
        ];
    }
}
