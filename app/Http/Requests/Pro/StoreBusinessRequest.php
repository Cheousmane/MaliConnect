<?php

namespace App\Http\Requests\Pro;

use App\Models\User;
use App\Rules\MaliPhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valide la création d'une fiche entreprise.
 */
class StoreBusinessRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'legal_name' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:20', new MaliPhoneNumber],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'commune' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=2048,max_height=2048'],
        ];
    }

    /**
     * Collaborateurs à inviter lors de la création.
     *
     * @return array<string, mixed>
     */
    public function memberRules(): array
    {
        return [
            'members' => ['nullable', 'array'],
            'members.*.user_id' => ['required', 'integer', Rule::exists(User::class, 'id')],
            'members.*.role' => ['required', 'string', Rule::in(['owner', 'manager', 'agent'])],
        ];
    }
}
