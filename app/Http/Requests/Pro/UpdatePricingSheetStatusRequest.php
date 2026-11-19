<?php

namespace App\Http\Requests\Pro;

use App\Enums\PricingSheetStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valide le changement de statut d'une fiche tarifaire.
 */
class UpdatePricingSheetStatusRequest extends FormRequest
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
            'status' => [
                'required',
                'string',
                Rule::in(array_map(fn (PricingSheetStatus $status) => $status->value, PricingSheetStatus::cases())),
            ],
        ];
    }
}
