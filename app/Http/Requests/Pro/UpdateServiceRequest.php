<?php

namespace App\Http\Requests\Pro;

/**
 * Valide la modification d'un service proposé.
 */
class UpdateServiceRequest extends ServiceRequest
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
