<?php

namespace App\Support\Identity;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Génère des identifiants d'URL uniques et lisibles.
 */
final class SlugGenerator
{
    /**
     * Construit un slug unique pour la table donnée.
     *
     * Un suffixe numérique est ajouté lorsque le slug est déjà utilisé.
     */
    public static function unique(string $table, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);

        if ($base === '') {
            $base = 'profil';
        }

        $slug = $base;
        $suffix = 1;

        while (self::exists($table, $slug, $ignoreId)) {
            $suffix++;
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }

    /**
     * Indique si le slug est déjà utilisé dans la table.
     */
    private static function exists(string $table, string $slug, ?int $ignoreId): bool
    {
        $query = DB::table($table)->where('slug', $slug);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
