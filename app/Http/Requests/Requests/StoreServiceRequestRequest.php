<?php

namespace App\Http\Requests\Requests;

use App\Models\ProviderProfile;
use App\Models\Service;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valide une demande de devis adressée à un professionnel.
 */
class StoreServiceRequestRequest extends FormRequest
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
            'provider_profile_id' => [
                'required',
                'integer',
                Rule::exists(ProviderProfile::class, 'id'),
                Rule::exists(ProviderProfile::class, 'id')->where('status', 'verified')->where('is_active', true),
            ],
            'service_id' => [
                'nullable',
                'integer',
                Rule::exists(Service::class, 'id'),
                Rule::exists(Service::class, 'id')->where('provider_profile_id', $this->input('provider_profile_id')),
            ],
            'pricing_item_id' => ['nullable', 'integer', Rule::exists('pricing_items', 'id')],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'min:20', 'max:3000'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'commune' => ['nullable', 'string', 'max:100'],
            'quartier' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'budget_min' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'budget_max' => ['nullable', 'integer', 'min:0', 'max:100000000', 'gte:budget_min'],
            'desired_date' => ['nullable', 'date', 'after:today'],
            'preferred_time' => ['nullable', 'string', 'max:20'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ];
    }
}
