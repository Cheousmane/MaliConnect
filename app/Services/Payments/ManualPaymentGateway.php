<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentGateway;
use App\Models\Payment;

/**
 * Passerelle manuelle : aucun appel externe.
 *
 * Un administrateur saisit la référence et confirme le paiement après
 * vérification hors ligne. C'est le mode par défaut tant qu'aucune
 * passerelle mobile money n'est intégrée.
 */
final class ManualPaymentGateway implements PaymentGateway
{
    public function code(): string
    {
        return 'manual';
    }

    public function initiate(Payment $payment): array
    {
        return [
            'external_reference' => 'MANUAL-'.$payment->reference,
            'redirect_url' => null,
        ];
    }

    public function verify(Payment $payment): bool
    {
        return $payment->status->value === 'paid';
    }

    public function handleWebhook(array $payload): bool
    {
        return false;
    }
}
