<?php

namespace App\Notifications;

use App\Models\ProviderReview;

/**
 * Notifie les gestionnaires d'un profil de la réception d'un nouvel avis.
 */
class NewReviewNotification extends ApplicationNotification
{
    public function __construct(private readonly ProviderReview $review) {}

    protected function payload(object $notifiable): array
    {
        return [
            'title' => 'Nouvel avis reçu',
            'description' => sprintf(
                '%s vous a attribué %d/5. L’avis sera visible après modération.',
                $this->review->customer?->name ?? 'Un client',
                $this->review->rating,
            ),
            'tone' => 'warning',
            'icon' => 'star',
            'url' => route('pro.reviews.index'),
            'resource' => ['type' => 'provider_review', 'id' => $this->review->id],
        ];
    }
}
