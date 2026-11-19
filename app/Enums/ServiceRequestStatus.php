<?php

namespace App\Enums;

/**
 * Statuts d'une demande de devis.
 */
enum ServiceRequestStatus: string
{
    case Pending = 'pending';

    case InProgress = 'in_progress';

    case Quoted = 'quoted';

    case Accepted = 'accepted';

    case Refused = 'refused';

    case Cancelled = 'cancelled';

    case Completed = 'completed';

    /**
     * Libellé affiché dans l'interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::InProgress => 'En cours',
            self::Quoted => 'Devis envoyé',
            self::Accepted => 'Acceptée',
            self::Refused => 'Refusée',
            self::Cancelled => 'Annulée',
            self::Completed => 'Terminée',
        };
    }

    /**
     * Ton visuel associé au statut dans l'interface.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::InProgress => 'info',
            self::Quoted => 'brand',
            self::Accepted => 'success',
            self::Refused => 'danger',
            self::Cancelled => 'neutral',
            self::Completed => 'success',
        };
    }

    /**
     * Indique si la demande reste modifiable par le client.
     */
    public function isEditable(): bool
    {
        return $this === self::Pending;
    }

    /**
     * Indique si la demande peut encore être annulée.
     */
    public function isCancellable(): bool
    {
        return in_array($this, [self::Pending, self::InProgress, self::Quoted], true);
    }

    /**
     * Indique si la demande est close.
     */
    public function isClosed(): bool
    {
        return in_array($this, [self::Refused, self::Cancelled, self::Completed], true);
    }
}
