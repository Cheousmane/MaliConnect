<?php

namespace App\Notifications;

use App\Models\ProviderProfile;
use App\Models\ServiceRequest;
use Illuminate\Support\Str;

/**
 * Notifie le client lorsqu'un professionnel répond à sa demande.
 */
class ProfessionalReplyNotification extends ApplicationNotification
{
    public function __construct(
        private readonly ServiceRequest $serviceRequest,
        private readonly ProviderProfile $profile,
        private readonly string $message,
    ) {}

    protected function payload(object $notifiable): array
    {
        return [
            'title' => 'Réponse du professionnel',
            'description' => sprintf(
                '%s a répondu à votre demande « %s » : %s',
                $this->profile->business_name,
                $this->serviceRequest->title,
                Str::limit($this->message, 120),
            ),
            'tone' => 'info',
            'icon' => 'message-square',
            'url' => route('requests.show', $this->serviceRequest),
            'resource' => ['type' => 'service_request', 'id' => $this->serviceRequest->id],
        ];
    }
}
