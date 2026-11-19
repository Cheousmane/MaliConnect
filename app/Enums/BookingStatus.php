<?php

namespace App\Enums;

/**
 * Statuts d'une réservation.
 */
enum BookingStatus: string
{
    case Pending = 'pending';

    case Confirmed = 'confirmed';

    case Completed = 'completed';

    case Cancelled = 'cancelled';

    /**
     * Libellé affiché dans l'interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Confirmed => 'Confirmée',
            self::Completed => 'Terminée',
            self::Cancelled => 'Annulée',
        };
    }

    /**
     * Ton visuel associé au statut dans l'interface.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'brand',
            self::Completed => 'success',
            self::Cancelled => 'neutral',
        };
    }

    /**
     * Indique si la réservation bloque encore le créneau du professionnel.
     */
    public function blocksSlot(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed], true);
    }

    /**
     * Indique si la réservation peut encore être annulée.
     */
    public function isCancellable(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed], true);
    }
}
