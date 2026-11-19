<?php

namespace App\Http\Requests\Pro;

use App\Enums\BillingCycle;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Déclaration d'un paiement pour une formule payante.
 *
 * Le propriétaire (profil indépendant ou entreprise) est choisi explicitement :
 * un abonnement ne peut jamais être souscrit pour un tiers.
 */
class SubscribePlanRequest extends FormRequest
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
        $maxKb = max(512, (int) config('maliconnect.subscriptions.proof_max_kb', 5120));

        return [
            'owner_type' => ['required', 'string', Rule::in(['profile', 'business'])],
            'owner_id' => ['required', 'integer', 'min:1'],
            'plan_id' => ['required', 'integer', Rule::exists('plans', 'id')],
            'billing_cycle' => ['required', 'string', Rule::enum(BillingCycle::class)],
            'method' => ['required', 'string', Rule::enum(PaymentMethod::class)],
            'external_reference' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:'.$maxKb],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'owner_type.in' => 'Le type de propriétaire est invalide.',
            'owner_id.required' => 'Sélectionnez le profil ou l’entreprise à abonner.',
            'plan_id.exists' => 'La formule sélectionnée n’existe pas.',
            'billing_cycle.required' => 'Choisissez une périodicité de facturation.',
            'method.required' => 'Choisissez un moyen de paiement.',
            'external_reference.required' => 'Indiquez la référence de votre transaction.',
            'proof.required' => 'Joignez une preuve de paiement lisible.',
            'proof.mimes' => 'La preuve doit être une image ou un PDF.',
            'proof.max' => 'La preuve ne doit pas dépasser :max Ko.',
        ];
    }
}
