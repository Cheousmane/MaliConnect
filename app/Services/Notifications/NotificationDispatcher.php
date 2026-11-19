<?php

namespace App\Services\Notifications;

use App\Models\ProviderProfile;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Diffuse les notifications aux bons destinataires.
 *
 * Centraliser l'envoi garantit que tous les gestionnaires d'un profil sont
 * notifiés et que les canaux restent configurables au même endroit.
 */
final class NotificationDispatcher
{
    /**
     * Notifie tous les gestionnaires d'un profil professionnel.
     */
    public function toProfileManagers(ProviderProfile $profile, ApplicationNotification $notification): void
    {
        $managers = $profile->managers();

        if ($managers->isNotEmpty()) {
            Notification::send($managers, $notification);
        }
    }

    /**
     * Notifie un utilisateur précis.
     */
    public function toUser(User $user, ApplicationNotification $notification): void
    {
        $user->notify($notification);
    }
}
