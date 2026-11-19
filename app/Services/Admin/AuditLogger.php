<?php

namespace App\Services\Admin;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Journalise les actions sensibles de l'administration.
 *
 * Chaque entrée conserve l'acteur, la cible, le contexte et l'adresse IP
 * d'origine. Le service ne prend aucune décision d'autorisation : il est
 * appelé après validation de la permission et de la policy concernées.
 */
final class AuditLogger
{
    /**
     * Enregistre une action d'administration.
     *
     * @param  array<string, mixed>  $properties
     */
    public function log(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?User $actor = null,
        ?Request $request = null,
    ): AdminAuditLog {
        $request ??= request();
        $actor ??= $request->user();

        return AdminAuditLog::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $properties === [] ? null : $properties,
            'ip_address' => $request->ip(),
        ]);
    }
}
