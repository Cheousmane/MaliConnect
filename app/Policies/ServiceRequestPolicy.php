<?php

namespace App\Policies;

use App\Enums\ServiceRequestStatus;
use App\Models\ServiceRequest;
use App\Models\User;

/**
 * Autorisations sur les demandes de devis.
 *
 * Le client et le professionnel n'accèdent qu'à leurs propres demandes ;
 * l'administration intervient par permission dédiée.
 */
class ServiceRequestPolicy
{
    /**
     * Autorise la consultation de la liste des demandes.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('service_requests.create') || $user->can('service_requests.respond');
    }

    /**
     * Autorise la consultation d'une demande.
     */
    public function view(User $user, ServiceRequest $request): bool
    {
        return $request->isOwnedByCustomer($user)
            || $request->isOwnedByProfessional($user)
            || $user->can('service_requests.manage');
    }

    /**
     * Autorise la création d'une demande.
     */
    public function create(User $user): bool
    {
        return $user->can('service_requests.create');
    }

    /**
     * Autorise la modification du contenu d'une demande.
     */
    public function update(User $user, ServiceRequest $request): bool
    {
        return $request->isOwnedByCustomer($user) && $request->status->isEditable();
    }

    /**
     * Autorise l'annulation d'une demande par le client.
     */
    public function cancel(User $user, ServiceRequest $request): bool
    {
        return $request->isOwnedByCustomer($user) && $request->status->isCancellable();
    }

    /**
     * Autorise le professionnel à répondre (question ou message).
     */
    public function respond(User $user, ServiceRequest $request): bool
    {
        return $request->isOwnedByProfessional($user)
            && $user->can('service_requests.respond')
            && ! $request->status->isClosed();
    }

    /**
     * Autorise le professionnel à envoyer un devis.
     */
    public function quote(User $user, ServiceRequest $request): bool
    {
        return $request->isOwnedByProfessional($user)
            && $user->can('service_requests.quote')
            && ! $request->status->isClosed();
    }

    /**
     * Autorise le client à accepter un devis.
     */
    public function acceptQuote(User $user, ServiceRequest $request): bool
    {
        return $request->isOwnedByCustomer($user)
            && $request->status === ServiceRequestStatus::Quoted;
    }

    /**
     * Autorise le client à refuser explicitement un devis.
     */
    public function rejectQuote(User $user, ServiceRequest $request): bool
    {
        return $request->isOwnedByCustomer($user)
            && $user->can('service_requests.create')
            && $request->status === ServiceRequestStatus::Quoted;
    }

    /**
     * Autorise le professionnel à clore la demande.
     */
    public function complete(User $user, ServiceRequest $request): bool
    {
        return $request->isOwnedByProfessional($user)
            && $user->can('service_requests.complete')
            && $request->status === ServiceRequestStatus::Accepted;
    }
}
