<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Socle commun des notifications applicatives.
 *
 * Le contenu est stocké par le canal « database » et pourra être réutilisé
 * par les canaux e-mail et push. Les classes concrètes ne décrivent que le
 * payload métier ; la liste des canaux reste centralisée en configuration.
 *
 * Les notifications sont mises en file d'attente : l'interface reste fluide
 * et les envois peuvent être repris en cas d'échec.
 */
abstract class ApplicationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Canaux de diffusion de la notification.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return config('notifications.channels', ['database']);
    }

    /**
     * Représentation enregistrée par le canal « database ».
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload($notifiable);
    }

    /**
     * Représentation e-mail, activable via la configuration des canaux.
     *
     * Le contenu réutilise le payload métier : les classes concrètes restent
     * indépendantes du canal de diffusion.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $payload = $this->payload($notifiable);

        $mail = (new MailMessage)
            ->subject((string) ($payload['title'] ?? config('app.name')))
            ->line((string) ($payload['description'] ?? ''));

        $url = $payload['url'] ?? null;

        if (is_string($url) && $url !== '') {
            $mail->action('Ouvrir sur MaliConnect', $url);
        }

        return $mail;
    }

    /**
     * Construit le contenu métier de la notification.
     *
     * @return array<string, mixed>
     */
    abstract protected function payload(object $notifiable): array;

    /**
     * Formate un montant en francs CFA.
     */
    protected function formatAmount(mixed $amount): string
    {
        return number_format((float) $amount, 0, ',', ' ').' FCFA';
    }

    /**
     * Formate une date pour l'interface française.
     */
    protected function formatDate(?CarbonInterface $date): string
    {
        return $date?->locale('fr')->translatedFormat('d F Y à H:i') ?? 'date non précisée';
    }
}
