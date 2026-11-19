<?php

namespace App\Http\Requests\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide un devis envoyé par un professionnel.
 */
class StoreQuoteRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Récupère les règles de validation applicables à la requête.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:20', 'max:3000'],
            'amount' => ['required', 'integer', 'min:0', 'max:100000000'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:10080'],
            'valid_until' => ['nullable', 'date', 'after:today'],
            'service_id' => ['nullable', 'integer'],
        ];
    }
}
