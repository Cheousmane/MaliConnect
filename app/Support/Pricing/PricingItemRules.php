<?php

namespace App\Support\Pricing;

use App\Enums\PricingType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Règles de validation partagées par les prestations tarifées.
 *
 * Le préfixe permet de réutiliser les mêmes règles pour un formulaire complet
 * (`items.*`) ou pour une seule prestation (`''`).
 */
final class PricingItemRules
{
    /**
     * Règles de validation d'une prestation.
     *
     * @return array<string, mixed>
     */
    public static function rules(?int $profileId = null, string $prefix = ''): array
    {
        return [
            $prefix.'id' => ['nullable', 'integer'],
            $prefix.'name' => ['required', 'string', 'max:150'],
            $prefix.'description' => ['nullable', 'string', 'max:2000'],
            $prefix.'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            $prefix.'service_id' => [
                'nullable',
                'integer',
                Rule::exists('services', 'id')->where(function ($query) use ($profileId) {
                    if ($profileId !== null) {
                        $query->where('provider_profile_id', $profileId);
                    }
                }),
            ],
            $prefix.'pricing_type' => [
                'required',
                'string',
                Rule::in(array_map(fn (PricingType $type) => $type->value, PricingType::cases())),
            ],
            $prefix.'price' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            $prefix.'price_min' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            $prefix.'price_max' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            $prefix.'billing_unit' => ['nullable', 'string', 'max:50'],
            $prefix.'conditions' => ['nullable', 'string', 'max:2000'],
            $prefix.'estimated_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
            $prefix.'is_available' => ['nullable', 'boolean'],
            $prefix.'requires_diagnostic' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Vérifie la cohérence des montants d'une ou plusieurs prestations.
     *
     * @param  array<int|string, array<string, mixed>>  $items
     */
    public static function validate(Validator $validator, array $items, string $prefix = ''): void
    {
        foreach ($items as $index => $item) {
            // Les règles d’un formulaire complet utilisent `items.*.` : le
            // caractère générique est remplacé par l’index réel pour que les
            // erreurs s’affichent sur la bonne prestation.
            $keyPrefix = $prefix === '' ? '' : str_replace('*', (string) $index, $prefix);

            if (! is_array($item)) {
                continue;
            }

            $type = PricingType::tryFrom((string) ($item['pricing_type'] ?? ''));
            $requiresDiagnostic = filter_var($item['requires_diagnostic'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $hasPrice = self::filled($item['price'] ?? null)
                || self::filled($item['price_min'] ?? null)
                || self::filled($item['price_max'] ?? null);

            if ($type?->requiresPrice() && ! $requiresDiagnostic && ! $hasPrice) {
                $validator->errors()->add(
                    $keyPrefix.'price',
                    'Un prix ou une fourchette est requis pour ce mode de tarification.',
                );
            }

            $min = $item['price_min'] ?? null;
            $max = $item['price_max'] ?? null;

            if (self::filled($min) && self::filled($max) && (float) $max < (float) $min) {
                $validator->errors()->add(
                    $keyPrefix.'price_max',
                    'Le prix maximum doit être supérieur ou égal au prix minimum.',
                );
            }
        }
    }

    /**
     * Indique si une valeur de prix est réellement renseignée.
     */
    private static function filled(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }
}
