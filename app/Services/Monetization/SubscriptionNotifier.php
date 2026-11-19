<?php

namespace App\Services\Monetization;

use App\Models\Business;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\ProviderProfile;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\PaymentConfirmedNotification;
use App\Notifications\PaymentReceivedNotification;
use App\Notifications\PaymentRejectedNotification;
use App\Notifications\PlanLimitReachedNotification;
use App\Notifications\RenewalNeededNotification;
use App\Notifications\SubscriptionActivatedNotification;
use App\Notifications\SubscriptionExpiredNotification;
use App\Notifications\SubscriptionExpiringSoonNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Diffuse les notifications du cycle de vie des abonnements.
 *
 * Les destinataires sont toujours les gestionnaires réels du propriétaire
 * concerné (profil ou entreprise), jamais l'ensemble des comptes.
 */
final class SubscriptionNotifier
{
    /**
     * Notifie le professionnel et les administrateurs du dépôt d'un paiement.
     */
    public function paymentReceived(Payment $payment): void
    {
        $payment->loadMissing(['plan', 'providerProfile', 'business', 'user']);

        $this->sendToUsers($payment->ownerUsers(), new PaymentReceivedNotification($payment));

        $this->administrators()->each(
            fn (User $admin) => $admin->notify(new PaymentReceivedNotification($payment, true)),
        );
    }

    /**
     * Notifie le professionnel de la confirmation de son paiement.
     */
    public function paymentConfirmed(Payment $payment): void
    {
        $payment->loadMissing(['plan', 'providerProfile', 'business', 'user']);

        $this->sendToUsers($payment->ownerUsers(), new PaymentConfirmedNotification($payment));
    }

    /**
     * Notifie le professionnel du refus de son paiement.
     */
    public function paymentRejected(Payment $payment): void
    {
        $payment->loadMissing(['plan', 'providerProfile', 'business', 'user']);

        $this->sendToUsers($payment->ownerUsers(), new PaymentRejectedNotification($payment));
    }

    /**
     * Notifie l'activation ou le renouvellement de l'abonnement.
     */
    public function subscriptionActivated(Subscription $subscription): void
    {
        $subscription->loadMissing(['planModel', 'providerProfile', 'business']);

        $this->sendToUsers($subscription->ownerUsers(), new SubscriptionActivatedNotification($subscription));
    }

    /**
     * Rappelle l'échéance prochaine.
     */
    public function subscriptionExpiringSoon(Subscription $subscription): void
    {
        $subscription->loadMissing(['planModel', 'providerProfile', 'business']);

        $this->sendToUsers($subscription->ownerUsers(), new SubscriptionExpiringSoonNotification($subscription));
    }

    /**
     * Notifie l'expiration effective.
     */
    public function subscriptionExpired(Subscription $subscription): void
    {
        $subscription->loadMissing(['planModel', 'providerProfile', 'business']);

        $this->sendToUsers($subscription->ownerUsers(), new SubscriptionExpiredNotification($subscription));
    }

    /**
     * Signale qu'un renouvellement est nécessaire pendant la grâce.
     */
    public function renewalNeeded(Subscription $subscription): void
    {
        $subscription->loadMissing(['planModel', 'providerProfile', 'business']);

        $this->sendToUsers($subscription->ownerUsers(), new RenewalNeededNotification($subscription));
    }

    /**
     * Informe qu'une limite de formule est atteinte.
     */
    public function limitReached(ProviderProfile|Business|User $owner, Plan $plan, string $limitLabel): void
    {
        $users = match (true) {
            $owner instanceof Business => $this->businessManagers($owner),
            $owner instanceof ProviderProfile => $owner->managers(),
            default => collect([$owner]),
        };

        $this->sendToUsers($users, new PlanLimitReachedNotification($plan, $limitLabel));
    }

    /**
     * Envoie une notification à une collection d'utilisateurs uniques.
     *
     * @param  Collection<int, User>  $users
     */
    private function sendToUsers(Collection $users, object $notification): void
    {
        $users = $users->filter()->unique('id');

        if ($users->isNotEmpty()) {
            Notification::send($users, $notification);
        }
    }

    /**
     * Administrateurs habilités à vérifier les paiements.
     *
     * @return Collection<int, User>
     */
    private function administrators(): Collection
    {
        return User::query()
            ->permission('payments.manage')
            ->get();
    }

    /**
     * Gestionnaires d'une entreprise.
     *
     * @return Collection<int, User>
     */
    private function businessManagers(Business $business): Collection
    {
        $users = collect();

        if ($business->owner !== null) {
            $users->push($business->owner);
        }

        $business->members()
            ->where('status', 'active')
            ->with('user')
            ->get()
            ->filter(fn ($member) => $member->canManageProfiles() && $member->user !== null)
            ->each(fn ($member) => $users->push($member->user));

        return $users->unique('id')->values();
    }
}
