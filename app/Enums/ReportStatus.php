<?php

namespace App\Enums;

/**
 * Statuts de traitement d'un signalement.
 */
enum ReportStatus: string
{
    case Pending = 'pending';

    case Reviewed = 'reviewed';

    case Resolved = 'resolved';

    case Dismissed = 'dismissed';

    /**
     * Libellé affiché dans l'interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Reviewed => 'En cours d’examen',
            self::Resolved => 'Traité',
            self::Dismissed => 'Classé sans suite',
        };
    }

    /**
     * Ton visuel associé au statut.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Reviewed => 'info',
            self::Resolved => 'success',
            self::Dismissed => 'neutral',
        };
    }

    /**
     * Indique si le signalement attend encore une décision.
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Reviewed], true);
    }
}
