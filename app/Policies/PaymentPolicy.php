<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

/**
 * Autorisations sur les paiements et leurs preuves.
 */
class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payments.view') || $user->can('payments.manage');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->can('payments.view') || $user->can('payments.manage');
    }

    public function manage(User $user, Payment $payment): bool
    {
        return $user->can('payments.manage');
    }

    /**
     * Consultation par le propriétaire du paiement.
     */
    public function viewOwn(User $user, Payment $payment): bool
    {
        return $payment->isOwnedBy($user);
    }

    /**
     * Téléchargement d'une preuve de paiement.
     *
     * Les administrateurs habilités y accèdent pour vérification ; le
     * professionnel concerné peut consulter ses propres justificatifs. Aucun
     * autre compte n'obtient d'accès, même avec l'URL.
     */
    public function downloadProof(User $user, Payment $payment): bool
    {
        return $user->can('payments.view')
            || $user->can('payments.manage')
            || $payment->isOwnedBy($user);
    }
}
