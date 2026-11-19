<?php

namespace App\Http\Requests\Pro;

/**
 * Valide la création d'un service proposé.
 */
class StoreServiceRequest extends ServiceRequest
{
    /**
     * Récupère les règles de validation applicables à la requête.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->serviceRules();
    }
}
