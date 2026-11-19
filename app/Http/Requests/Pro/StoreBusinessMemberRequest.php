<?php

namespace App\Http\Requests\Pro;

use App\Enums\BusinessMemberRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valide l'ajout ou la modification d'un collaborateur d'entreprise.
 */
class StoreBusinessMemberRequest extends FormRequest
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
            'user_id' => [
                'required',
                'integer',
                Rule::exists(User::class, 'id'),
                Rule::unique('business_members', 'user_id')->where('business_id', $this->route('business')->id),
            ],
            'role' => ['required', 'string', Rule::enum(BusinessMemberRole::class)],
        ];
    }
}
