<?php

namespace App\Support\Notifications;

use Illuminate\Notifications\DatabaseNotification;

/**
 * Transforme une notification Laravel en données exploitables par l'interface.
 */
final class NotificationPresenter
{
    /**
     * Représente une notification pour le menu et le centre de notifications.
     *
     * @return array<string, mixed>
     */
    public static function item(DatabaseNotification $notification): array
    {
        $data = $notification->data ?? [];

        return [
            'id' => $notification->id,
            'title' => (string) ($data['title'] ?? 'Notification'),
            'description' => $data['description'] ?? null,
            'time' => $notification->created_at?->locale('fr')->diffForHumans(),
            'read' => $notification->read_at !== null,
            'tone' => (string) ($data['tone'] ?? 'neutral'),
            'icon' => (string) ($data['icon'] ?? 'bell'),
            'url' => $data['url'] ?? null,
        ];
    }
}
