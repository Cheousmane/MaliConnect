<?php

namespace App\Policies;

use App\Models\Plan;
use App\Models\User;

/**
 * Autorisations sur les formules commerciales.
 *
 * La grille tarifaire est administrée exclusivement par les comptes disposant
 * des permissions dédiées. Les professionnels la consultent via leur propre
 * page de comparaison, sans passer par ces policies.
 */
class PlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('plans.view') || $user->can('plans.manage');
    }

    public function view(User $user, Plan $plan): bool
    {
        return $user->can('plans.view') || $user->can('plans.manage');
    }

    public function manage(User $user, Plan $plan): bool
    {
        return $user->can('plans.manage');
    }
}
