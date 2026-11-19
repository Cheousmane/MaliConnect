<?php

namespace App\Policies;

use App\Models\AdminAuditLog;
use App\Models\User;

/**
 * Autorisations sur le journal d'audit.
 */
class AdminAuditLogPolicy
{
    /**
     * Autorise la consultation du journal.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('audit.view');
    }

    /**
     * Autorise la consultation d'une entrée du journal.
     */
    public function view(User $user, AdminAuditLog $log): bool
    {
        return $user->can('audit.view');
    }
}
