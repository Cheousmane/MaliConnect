<?php

namespace App\Console\Commands;

use App\Services\Monetization\SubscriptionService;
use Illuminate\Console\Command;

/**
 * Traite les échéances, la période de grâce, les expirations et les rappels.
 *
 * La commande est planifiée dans routes/console.php. Elle est idempotente :
 * chaque événement n'est enregistré qu'une seule fois.
 */
class ProcessSubscriptions extends Command
{
    protected $signature = 'subscriptions:process';

    protected $description = 'Traite le cycle de vie des abonnements (grâce, expirations, rappels)';

    public function handle(SubscriptionService $subscriptions): int
    {
        $counts = $subscriptions->processLifecycle();

        $this->info(sprintf(
            'Abonnements traités : %d changement(s) programmé(s), %d passage(s) en retard, %d expiration(s), %d rappel(s).',
            $counts['scheduled'],
            $counts['past_due'],
            $counts['expired'],
            $counts['reminders'],
        ));

        return self::SUCCESS;
    }
}
