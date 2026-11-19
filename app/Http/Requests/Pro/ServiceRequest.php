<?php

namespace App\Http\Requests\Pro;

use App\Models\Service;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Règles communes aux formulaires de service proposé.
 */
abstract class ServiceRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation d'un service.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function serviceRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price_type' => ['required', 'string', Rule::in(Service::PRICE_TYPES)],
            'price_min' => ['nullable', 'integer', 'min:0', 'max:100000000', 'required_unless:price_type,quote'],
            'price_max' => ['nullable', 'integer', 'min:0', 'max:100000000', 'gte:price_min', 'required_if:price_type,range'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:10080'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
