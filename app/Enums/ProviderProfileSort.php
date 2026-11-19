<?php

namespace App\Enums;

/**
 * Tris proposés par la recherche publique.
 */
enum ProviderProfileSort: string
{
    case Relevance = 'relevance';

    case Rating = 'rating';

    case Recent = 'recent';

    /**
     * Libellé affiché dans l'interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Relevance => 'Pertinence',
            self::Rating => 'Note',
            self::Recent => 'Nouveautés',
        };
    }

    /**
     * Indique si le tri dépend d'un mot-clé.
     */
    public function requiresKeyword(): bool
    {
        return $this === self::Relevance;
    }
}
