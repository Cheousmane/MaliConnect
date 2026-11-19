<?php

namespace App\Policies;

use App\Models\User;

/**
 * Autorisations d'accès aux comptes utilisateurs.
 *
 * Chaque action est contrôlée par une permission granular : un utilisateur
 * ne peut gérer que les comptes qu'il est autorisé à administrer, et jamais
 * son propre statut ni ses propres rôles.
 */
class UserPolicy
{
    /**
     * Autorise la consultation de la liste des comptes.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('users.viewAny');
    }

    /**
     * Autorise la consultation d'un compte.
     */
    public function view(User $user, User $account): bool
    {
        return $user->id === $account->id || $user->can('users.view');
    }

    /**
     * Autorise la mise à jour d'un compte.
     */
    public function update(User $user, User $account): bool
    {
        return $user->id === $account->id || $user->can('users.update');
    }

    /**
     * Autorise le changement de statut d'un compte (activation, suspension).
     */
    public function changeStatus(User $user, User $account): bool
    {
        return $user->id !== $account->id && $user->can('users.suspend');
    }

    /**
     * Autorise l'attribution ou le retrait de rôles.
     */
    public function assignRoles(User $user, User $account): bool
    {
        return $user->id !== $account->id && $user->can('users.assign_roles');
    }

    /**
     * Autorise la suppression d'un compte.
     */
    public function delete(User $user, User $account): bool
    {
        return $user->id !== $account->id && $user->can('users.delete');
    }
}
