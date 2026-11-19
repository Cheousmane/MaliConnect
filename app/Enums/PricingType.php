<?php

namespace App\Enums;

/**
 * Modes de tarification d'une prestation.
 *
 * Le mode « quote » correspond aux prestations dont le prix dépend d'un
 * diagnostic ou d'une demande de devis : aucun montant fictif n'est affiché.
 */
enum PricingType: string
{
    case Fixed = 'fixed';

    case Hourly = 'hourly';

    case Daily = 'daily';

    case Intervention = 'intervention';

    case Quote = 'quote';

    /**
     * Libellé affiché dans l'interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Forfait',
            self::Hourly => 'Par heure',
            self::Daily => 'Par journée',
            self::Intervention => 'Par intervention',
            self::Quote => 'Sur devis',
        };
    }

    /**
     * Suffixe d'unité affiché à côté du prix.
     */
    public function unitSuffix(): ?string
    {
        return match ($this) {
            self::Fixed => null,
            self::Hourly => '/ heure',
            self::Daily => '/ journée',
            self::Intervention => '/ intervention',
            self::Quote => null,
        };
    }

    /**
     * Indique si un montant est obligatoire pour ce mode.
     */
    public function requiresPrice(): bool
    {
        return $this !== self::Quote;
    }

    /**
     * Indique si la prestation est proposée sur devis.
     */
    public function isQuote(): bool
    {
        return $this === self::Quote;
    }

    /**
     * Valeurs proposées dans les formulaires.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'requires_price' => $type->requiresPrice(),
                'unit_suffix' => $type->unitSuffix(),
            ])
            ->values()
            ->all();
    }
}
