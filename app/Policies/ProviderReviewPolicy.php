<?php

namespace App\Policies;

use App\Models\ProviderBooking;
use App\Models\ProviderReview;
use App\Models\User;

/**
 * Autorisations sur les avis clients.
 *
 * Un client ne dépose un avis que sur sa propre prestation terminée. La
 * modération est réservée à l'administration, tandis que la réponse publique
 * appartient aux gestionnaires du profil évalué.
 */
class ProviderReviewPolicy
{
    /**
     * Autorise la consultation de la liste des avis.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('reviews.view')
            || $user->can('reviews.manage')
            || $user->can('reviews.create');
    }

    /**
     * Autorise la consultation d'un avis.
     */
    public function view(User $user, ProviderReview $review): bool
    {
        return $review->customer_id === $user->id
            || $review->providerProfile?->isManagedBy($user)
            || $user->can('reviews.manage');
    }

    /**
     * Autorise le dépôt d'un avis sur une réservation éligible.
     */
    public function create(User $user, ProviderBooking $booking): bool
    {
        return $user->can('reviews.create') && $booking->isReviewableBy($user);
    }

    /**
     * Autorise la réponse publique du professionnel.
     */
    public function respond(User $user, ProviderReview $review): bool
    {
        return $review->providerProfile?->isManagedBy($user)
            && $user->can('reviews.respond')
            && $review->canBeReplied();
    }

    /**
     * Autorise la décision de modération.
     */
    public function moderate(User $user, ProviderReview $review): bool
    {
        return $user->can('reviews.manage');
    }
}
