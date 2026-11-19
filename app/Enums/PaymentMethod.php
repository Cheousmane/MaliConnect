<?php

namespace App\Enums;

/**
 * Moyens de paiement acceptés.
 *
 * Le paiement manuel reste la référence tant qu'aucun fournisseur Mobile
 * Money n'est branché. Orange Money est déjà prévu dans l'énumération afin
 * que l'architecture puisse l'accueillir sans migration de données.
 */
enum PaymentMethod: string
{
    case MobileMoney = 'mobile_money';

    case OrangeMoney = 'orange_money';

    case BankTransfer = 'bank_transfer';

    case Cash = 'cash';

    case Other = 'other';

    /**
     * Libellé affiché dans l'interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::MobileMoney => 'Mobile Money',
            self::OrangeMoney => 'Orange Money',
            self::BankTransfer => 'Virement bancaire',
            self::Cash => 'Espèces',
            self::Other => 'Autre',
        };
    }

    /**
     * Indique si le moyen de paiement est traité manuellement.
     */
    public function isManual(): bool
    {
        return in_array($this, [self::MobileMoney, self::OrangeMoney, self::BankTransfer, self::Cash, self::Other], true);
    }
}
