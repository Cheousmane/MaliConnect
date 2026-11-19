<?php

namespace App\Http\Requests\Pro;

use App\Enums\PricingSheetStatus;
use App\Support\Pricing\PricingItemRules;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valide la création d'une fiche tarifaire et de ses prestations.
 */
class StorePricingSheetRequest extends FormRequest
{
    /**
     * L'autorisation est assurée par la policy dans le contrôleur.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider_profile_id' => ['required', 'integer', Rule::exists('provider_profiles', 'id')],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'currency' => ['nullable', 'string', Rule::in(['XOF', 'EUR', 'USD'])],
            'general_conditions' => ['nullable', 'string', 'max:5000'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:today'],
            'status' => ['nullable', 'string', Rule::in(array_map(fn (PricingSheetStatus $status) => $status->value, PricingSheetStatus::cases()))],
            'items' => ['nullable', 'array', 'max:100'],
            ...PricingItemRules::rules($this->integer('provider_profile_id'), 'items.*.'),
        ];
    }

    /**
     * Contrôles de cohérence après validation.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            PricingItemRules::validate($validator, $this->input('items', []), 'items.*.');
        });
    }
}
