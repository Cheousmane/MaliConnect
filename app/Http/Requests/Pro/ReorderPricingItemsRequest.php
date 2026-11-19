<?php

namespace App\Http\Requests\Pro;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide la réorganisation des prestations d'une fiche.
 */
class ReorderPricingItemsRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*' => ['required', 'integer', 'distinct', 'exists:pricing_items,id'],
        ];
    }
}
