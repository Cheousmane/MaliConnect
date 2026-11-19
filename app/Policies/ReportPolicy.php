<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

/**
 * Autorisations sur les signalements.
 */
class ReportPolicy
{
    /**
     * Autorise le dépôt d'un signalement.
     */
    public function create(User $user): bool
    {
        return $user->can('reports.create');
    }

    /**
     * Autorise la consultation de la liste des signalements.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('reports.view') || $user->can('reports.manage');
    }

    /**
     * Autorise la consultation d'un signalement.
     */
    public function view(User $user, Report $report): bool
    {
        return $report->reporter_id === $user->id
            || $user->can('reports.view')
            || $user->can('reports.manage');
    }

    /**
     * Autorise le traitement d'un signalement.
     */
    public function manage(User $user, Report $report): bool
    {
        return $user->can('reports.manage');
    }
}
