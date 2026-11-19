<?php

namespace App\Providers;

use App\Models\AdminAuditLog;
use App\Models\Business;
use App\Models\Payment;
use App\Models\ProviderBooking;
use App\Models\ProviderProfile;
use App\Models\ProviderReview;
use App\Models\Report;
use App\Models\ServiceRequest;
use App\Models\Subscription;
use App\Models\User;
use App\Policies\AdminAuditLogPolicy;
use App\Policies\BusinessPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProviderBookingPolicy;
use App\Policies\ProviderProfilePolicy;
use App\Policies\ProviderReviewPolicy;
use App\Policies\ReportPolicy;
use App\Policies\ServiceRequestPolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Enregistre les services de l'application.
     */
    public function register(): void
    {
        //
    }

    /**
     * Initialise les services de l'application.
     */
    public function boot(): void
    {
        // Les policies pilotent l'accès aux comptes, profils, entreprises,
        // demandes de devis et réservations.
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(ProviderProfile::class, ProviderProfilePolicy::class);
        Gate::policy(Business::class, BusinessPolicy::class);
        Gate::policy(ServiceRequest::class, ServiceRequestPolicy::class);
        Gate::policy(ProviderBooking::class, ProviderBookingPolicy::class);
        Gate::policy(ProviderReview::class, ProviderReviewPolicy::class);
        Gate::policy(Subscription::class, SubscriptionPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Report::class, ReportPolicy::class);
        Gate::policy(AdminAuditLog::class, AdminAuditLogPolicy::class);

        // Le rôle « super-admin » porte toutes les permissions du catalogue :
        // aucun contournement global n'est appliqué, afin que les protections
        // d'auto-isolation restent effectives.
    }
}
