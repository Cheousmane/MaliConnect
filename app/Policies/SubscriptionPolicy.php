<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

/**
 * Autorisations sur les abonnements professionnels.
 *
 * Deux usages coexistent :
 *   - l'administration, protégée par les permissions `subscriptions.*` ;
 *   - l'espace professionnel, strictement limité au propriétaire réel de
 *     l'abonnement (profil indépendant ou entreprise).
 */
class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('subscriptions.view') || $user->can('subscriptions.manage');
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return $user->can('subscriptions.view') || $user->can('subscriptions.manage');
    }

    public function manage(User $user, Subscription $subscription): bool
    {
        return $user->can('subscriptions.manage');
    }

    /**
     * Consultation par le propriétaire ou un gestionnaire habilité.
     */
    public function viewOwn(User $user, Subscription $subscription): bool
    {
        return $subscription->isOwnedBy($user);
    }

    /**
     * Résiliation par le propriétaire ou un gestionnaire habilité.
     */
    public function cancelOwn(User $user, Subscription $subscription): bool
    {
        return $subscription->isOwnedBy($user);
    }
}
