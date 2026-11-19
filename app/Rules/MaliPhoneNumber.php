<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valide un numéro de téléphone malien.
 *
 * Formats acceptés : 12345678, 22312345678, +223 12 34 56 78.
 * Le numéro comporte huit chiffres, quel que soit leur préfixe.
 */
class MaliPhoneNumber implements ValidationRule
{
    /**
     * Vérifie la validité du numéro de téléphone.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail('Le numéro de téléphone est obligatoire.');

            return;
        }

        if (self::normalize($value) === null) {
            $fail('Le numéro doit être un numéro malien valide : 8 chiffres.');
        }
    }

    /**
     * Normalise un numéro malien au format international +223XXXXXXXX.
     *
     * Retourne null lorsque le numéro n'est pas exploitable.
     */
    public static function normalize(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        // L'indicatif international du Mali est retiré avant le contrôle.
        if (str_starts_with($digits, '223')) {
            $digits = substr($digits, 3);
        }

        if (! preg_match('/^\d{8}$/', $digits)) {
            return null;
        }

        return '+223'.$digits;
    }
}
