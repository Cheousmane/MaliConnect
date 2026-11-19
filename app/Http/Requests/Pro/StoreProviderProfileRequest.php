<?php

namespace App\Http\Requests\Pro;

/**
 * Valide la création d'un profil professionnel.
 */
class StoreProviderProfileRequest extends ProviderProfileRequest
{
    /**
     * Récupère les règles de validation applicables à la requête.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->profileRules();
    }
}
