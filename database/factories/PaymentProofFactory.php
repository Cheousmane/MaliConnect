<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentProof>
 */
class PaymentProofFactory extends Factory
{
    protected $model = PaymentProof::class;

    /**
     * Définit l'état par défaut du modèle.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'uploaded_by' => User::factory(),
            'disk' => 'local',
            'path' => 'payments/proofs/'.fake()->uuid().'.jpg',
            'original_name' => 'preuve-paiement.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 102400,
        ];
    }
}
