<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Support\Str;

/**
 * Notifie les gestionnaires du profil lorsqu'un client envoie un message.
 */
class NewMessageNotification extends ApplicationNotification
{
    public function __construct(
        private readonly ServiceRequest $serviceRequest,
        private readonly string $message,
    ) {}

    protected function payload(object $notifiable): array
    {
        $customerName = $this->serviceRequest->customer?->name ?? 'Un client';

        return [
            'title' => 'Nouveau message client',
            'description' => sprintf(
                '%s a écrit au sujet de « %s » : %s',
                $customerName,
                $this->serviceRequest->title,
                Str::limit($this->message, 120),
            ),
            'tone' => 'info',
            'icon' => 'message-square',
            'url' => route('pro.requests.show', $this->serviceRequest),
            'resource' => ['type' => 'service_request', 'id' => $this->serviceRequest->id],
        ];
    }
}
