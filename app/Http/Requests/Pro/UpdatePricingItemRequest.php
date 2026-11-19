<?php

namespace App\Http\Requests\Pro;

use App\Models\PricingSheet;
use App\Support\Pricing\PricingItemRules;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide la modification d'une prestation tarifée.
 */
class UpdatePricingItemRequest extends FormRequest
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
        return PricingItemRules::rules($this->profileId());
    }

    /**
     * Contrôles de cohérence après validation.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            PricingItemRules::validate($validator, [$this->all()]);
        });
    }

    /**
     * Profil propriétaire de la fiche.
     */
    private function profileId(): ?int
    {
        /** @var PricingSheet|null $sheet */
        $sheet = $this->route('sheet');

        return $sheet?->provider_profile_id;
    }
}
