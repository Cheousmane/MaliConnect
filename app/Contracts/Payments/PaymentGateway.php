<?php

namespace App\Contracts\Payments;

use App\Models\Payment;

/**
 * Contrat d'une passerelle de paiement.
 *
 * Les intégrations mobile money futures implémenteront cette interface
 * sans modifier le cœur métier. Tant qu'aucune passerelle n'est disponible,
 * le `ManualPaymentGateway` permet à un administrateur de vérifier un
 * paiement manuellement.
 */
interface PaymentGateway
{
    /**
     * Identifiant technique de la passerelle.
     */
    public function code(): string;

    /**
     * Initialise un paiement et retourne la référence externe.
     *
     * @return array{external_reference: string, redirect_url: ?string}
     */
    public function initiate(Payment $payment): array;

    /**
     * Vérifie un paiement à partir de sa référence externe.
     */
    public function verify(Payment $payment): bool;

    /**
     * Traite un webhook entrant. Retourne true si le paiement est confirmé.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload): bool;
}
