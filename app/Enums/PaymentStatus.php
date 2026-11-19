<?php

namespace App\Enums;

/**
 * Statuts d'un paiement.
 */
enum PaymentStatus: string
{
    case Pending = 'pending';

    case Paid = 'paid';

    case Rejected = 'rejected';

    case Failed = 'failed';

    case Refunded = 'refunded';

    /**
     * Libellé affiché dans l'interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Paid => 'Payé',
            self::Rejected => 'Refusé',
            self::Failed => 'Échoué',
            self::Refunded => 'Remboursé',
        };
    }

    /**
     * Ton visuel associé au statut.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Rejected => 'danger',
            self::Failed => 'danger',
            self::Refunded => 'neutral',
        };
    }

    /**
     * Indique si le paiement alimente les revenus enregistrés.
     */
    public function countsAsRevenue(): bool
    {
        return $this === self::Paid;
    }

    /**
     * Indique si le paiement peut encore être accepté ou refusé.
     */
    public function isReviewable(): bool
    {
        return $this === self::Pending;
    }
}
