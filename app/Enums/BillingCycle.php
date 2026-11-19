<?php

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * Périodicité de facturation d'un abonnement.
 */
enum BillingCycle: string
{
    case Monthly = 'monthly';

    case Yearly = 'yearly';

    /**
     * Libellé affiché dans l'interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Mensuel',
            self::Yearly => 'Annuel',
        };
    }

    /**
     * Libellé de la durée.
     */
    public function durationLabel(): string
    {
        return match ($this) {
            self::Monthly => 'mois',
            self::Yearly => 'an',
        };
    }

    /**
     * Nombre de mois couverts par une période.
     */
    public function months(): int
    {
        return match ($this) {
            self::Monthly => 1,
            self::Yearly => 12,
        };
    }

    /**
     * Calcule la prochaine échéance à partir d'une date.
     */
    public function addTo(CarbonInterface $date): CarbonInterface
    {
        return $this === self::Yearly
            ? $date->copy()->addYear()
            : $date->copy()->addMonth();
    }

    /**
     * Liste des périodicités proposées à l'interface.
     *
     * @return list<array{value: string, label: string, duration_label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $cycle) => [
                'value' => $cycle->value,
                'label' => $cycle->label(),
                'duration_label' => $cycle->durationLabel(),
            ],
            self::cases(),
        );
    }
}
