<?php

namespace App\Http\Requests\Pro;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Demande de résiliation d'un abonnement par son propriétaire.
 */
class CancelSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
