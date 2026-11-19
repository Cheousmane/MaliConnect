<?php

namespace App\Notifications;

use App\Models\ServiceRequest;

/**
 * Notifie les gestionnaires d'un profil d'une nouvelle demande de devis.
 */
class NewServiceRequestNotification extends ApplicationNotification
{
    public function __construct(private readonly ServiceRequest $serviceRequest) {}

    protected function payload(object $notifiable): array
    {
        $customer = $this->serviceRequest->customer?->name ?? 'Un client';

        return [
            'title' => 'Nouvelle demande de devis',
            'description' => sprintf('%s vous a adressé une demande : « %s ».', $customer, $this->serviceRequest->title),
            'tone' => 'brand',
            'icon' => 'clipboard-list',
            'url' => route('pro.requests.show', $this->serviceRequest),
            'resource' => ['type' => 'service_request', 'id' => $this->serviceRequest->id],
        ];
    }
}
