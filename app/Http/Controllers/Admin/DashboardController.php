<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
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
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tableau de bord analytique de l'administration.
 *
 * Tous les indicateurs sont calculés à partir des données réellement
 * enregistrées. Aucun montant, aucune tendance et aucun volume ne sont
 * simulés : une base vide affiche naturellement des zéros.
 */
class DashboardController extends Controller
{
    /**
     * Affiche les indicateurs et les dernières activités.
     */
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('statistics.view'), 403);

        $paidRevenue = (float) Payment::query()->paid()->sum('amount');

        $monthlyRevenue = collect(range(5, 0))
            ->map(function (int $offset): array {
                $start = now()->startOfMonth()->subMonths($offset);
                $end = $start->copy()->endOfMonth();

                return [
                    'label' => $start->translatedFormat('M Y'),
                    'amount' => (float) Payment::query()
                        ->paid()
                        ->whereBetween('paid_at', [$start, $end])
                        ->sum('amount'),
                ];
            })
            ->values()
            ->all();

        $reminderDays = max(1, (int) config('maliconnect.subscriptions.reminder_days', 7));

        $accessibleSubscriptions = Subscription::query()
            ->grantingAccess()
            ->with('planModel:id,code,name')
            ->get();

        $planCount = fn (string $code): int => $accessibleSubscriptions
            ->filter(fn (Subscription $subscription) => ($subscription->planModel?->code ?? $subscription->plan) === $code)
            ->count();

        // Un compte sans abonnement ouvrant droit aux fonctionnalités est
        // réellement sur la formule FREE, même sans ligne d'abonnement.
        $freeProfiles = ProviderProfile::query()
            ->whereNull('business_id')
            ->whereDoesntHave('subscriptions', fn ($query) => $query->whereNull('business_id')->grantingAccess())
            ->count();

        $freeBusinesses = Business::query()
            ->whereDoesntHave('subscriptions', fn ($query) => $query->grantingAccess())
            ->count();

        // Revenu mensuel récurrent : les formules annuelles sont lissées sur
        // douze mois. Les formules gratuites et les paiements en attente sont
        // exclus de ce calcul.
        $mrr = $accessibleSubscriptions
            ->filter(fn (Subscription $subscription) => ! ($subscription->planModel?->isFree() ?? $subscription->plan === 'free'))
            ->sum(fn (Subscription $subscription) => (float) $subscription->amount / max(1, $subscription->billing_cycle?->months() ?? 1));

        $subscriptions = [
            'active' => Subscription::query()->where('status', SubscriptionStatus::Active)->count(),
            'trialing' => Subscription::query()->where('status', SubscriptionStatus::Trialing)->count(),
            'with_access' => Subscription::query()->grantingAccess()->count(),
            'past_due' => Subscription::query()->where('status', SubscriptionStatus::PastDue)->count(),
            'expired' => Subscription::query()->where('status', SubscriptionStatus::Expired)->count(),
            'suspended' => Subscription::query()->where('status', SubscriptionStatus::Suspended)->count(),
            'pending_payments' => Payment::query()->where('status', PaymentStatus::Pending)->count(),
            'pending_payments_amount' => (float) Payment::query()->where('status', PaymentStatus::Pending)->sum('amount'),
            'renewals_upcoming' => Subscription::query()
                ->active()
                ->whereNotNull('ends_at')
                ->whereBetween('ends_at', [now(), now()->addDays($reminderDays)])
                ->count(),
            'mrr' => (float) $mrr,
            'revenue_total' => $paidRevenue,
            'revenue_month' => (float) Payment::query()
                ->paid()
                ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount'),
            'plans' => [
                'free' => $freeProfiles + $freeBusinesses,
                'pro' => $planCount('pro'),
                'business' => $planCount('business'),
            ],
        ];

        $oldestPendingDays = static function ($query): ?int {
            $date = $query->min('created_at');

            return $date ? now()->diffInDays($date) : null;
        };

        $pending = [
            'profiles' => ProviderProfile::query()->pendingReview()->count(),
            'businesses' => Business::query()->where('status', 'pending')->count(),
            'reviews' => ProviderReview::query()->pending()->count(),
            'reports' => Report::query()->open()->count(),
            'payments' => $subscriptions['pending_payments'],
            'oldest_profiles_days' => $oldestPendingDays(ProviderProfile::query()->pendingReview()),
            'oldest_businesses_days' => $oldestPendingDays(Business::query()->where('status', 'pending')),
            'oldest_reviews_days' => $oldestPendingDays(ProviderReview::query()->pending()),
            'oldest_reports_days' => $oldestPendingDays(Report::query()->open()),
            'oldest_payments_days' => $oldestPendingDays(Payment::query()->where('status', PaymentStatus::Pending)),
        ];

        $monthStart = now()->startOfMonth();
        $prevMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $prevMonthEnd = now()->subMonthNoOverflow()->endOfMonth();

        $trend = fn ($currentQuery, $previousQuery): array => [
            'current' => (float) $currentQuery,
            'previous' => (float) $previousQuery,
        ];

        $trends = [
            'users' => $trend(
                User::query()->where('created_at', '>=', $monthStart)->count(),
                User::query()->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])->count(),
            ),
            'requests' => $trend(
                ServiceRequest::query()->where('created_at', '>=', $monthStart)->count(),
                ServiceRequest::query()->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])->count(),
            ),
            'bookings' => $trend(
                ProviderBooking::query()->where('created_at', '>=', $monthStart)->count(),
                ProviderBooking::query()->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])->count(),
            ),
            'revenue' => $trend(
                (float) Payment::query()->paid()->whereBetween('paid_at', [$monthStart, now()])->sum('amount'),
                (float) Payment::query()->paid()->whereBetween('paid_at', [$prevMonthStart, $prevMonthEnd])->sum('amount'),
            ),
        ];

        $alerts = [];
        if ($pending['payments'] > 0) {
            $alerts[] = [
                'tone' => 'warning',
                'title' => 'Paiements à vérifier',
                'description' => $pending['payments'].' paiement(s) en attente, total '.number_format($subscriptions['pending_payments_amount'], 0, ',', ' ').' FCFA.',
                'href' => route('admin.payments.index', ['status' => 'pending']),
            ];
        }
        if ($subscriptions['past_due'] > 0) {
            $alerts[] = [
                'tone' => 'danger',
                'title' => 'Abonnements en retard',
                'description' => $subscriptions['past_due'].' abonnement(s) en retard de paiement.',
                'href' => route('admin.subscriptions.index'),
            ];
        }
        if ($pending['profiles'] > 0) {
            $alerts[] = [
                'tone' => ($pending['oldest_profiles_days'] ?? 0) >= 3 ? 'danger' : 'warning',
                'title' => 'Profils à vérifier',
                'description' => $pending['profiles'].' profil(s) en attente'.(($pending['oldest_profiles_days'] ?? null) !== null ? ', plus ancien depuis '.$pending['oldest_profiles_days'].' j.' : '.'),
                'href' => route('admin.provider-profiles.index'),
            ];
        }
        if ($pending['reports'] > 0) {
            $alerts[] = [
                'tone' => 'warning',
                'title' => 'Signalements ouverts',
                'description' => $pending['reports'].' signalement(s) à traiter.',
                'href' => route('admin.reports.index'),
            ];
        }

        return Inertia::render('admin/Dashboard', [
            'metrics' => [
                [
                    'key' => 'users',
                    'label' => 'Utilisateurs',
                    'value' => User::query()->count(),
                    'description' => 'Comptes enregistrés sur la plateforme.',
                    'tone' => 'brand',
                ],
                [
                    'key' => 'professionals',
                    'label' => 'Professionnels',
                    'value' => ProviderProfile::query()->count(),
                    'description' => 'Profils professionnels créés.',
                    'tone' => 'info',
                ],
                [
                    'key' => 'businesses',
                    'label' => 'Entreprises',
                    'value' => Business::query()->count(),
                    'description' => 'Structures enregistrées.',
                    'tone' => 'brand',
                ],
                [
                    'key' => 'pending_profiles',
                    'label' => 'Profils à vérifier',
                    'value' => ProviderProfile::query()->pendingReview()->count(),
                    'description' => 'Dossiers en attente de décision.',
                    'tone' => 'warning',
                ],
                [
                    'key' => 'requests',
                    'label' => 'Demandes',
                    'value' => ServiceRequest::query()->count(),
                    'description' => 'Demandes de devis déposées.',
                    'tone' => 'info',
                ],
                [
                    'key' => 'bookings',
                    'label' => 'Réservations',
                    'value' => ProviderBooking::query()->count(),
                    'description' => 'Créneaux réservés.',
                    'tone' => 'brand',
                ],
                [
                    'key' => 'completed_bookings',
                    'label' => 'Prestations terminées',
                    'value' => ProviderBooking::query()->where('status', BookingStatus::Completed)->count(),
                    'description' => 'Réservations réellement clôturées.',
                    'tone' => 'success',
                ],
                [
                    'key' => 'active_subscriptions',
                    'label' => 'Abonnements avec accès',
                    'value' => Subscription::query()->grantingAccess()->count(),
                    'description' => 'Abonnements actifs ou en essai gratuit.',
                    'tone' => 'success',
                ],
                [
                    'key' => 'revenue',
                    'label' => 'Revenus enregistrés',
                    'value' => $paidRevenue,
                    'description' => 'Somme des paiements réellement encaissés.',
                    'tone' => 'success',
                    'is_money' => true,
                ],
            ],
            'revenue' => [
                'total' => $paidRevenue,
                'currency' => 'XOF',
                'monthly' => $monthlyRevenue,
            ],
            'subscriptions' => $subscriptions,
            'pending' => $pending,
            'alerts' => $alerts,
            'trends' => $trends,
            'recentRequests' => ServiceRequest::query()
                ->with(['customer:id,name', 'providerProfile:id,business_name'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (ServiceRequest $serviceRequest) => [
                    'id' => $serviceRequest->id,
                    'reference' => $serviceRequest->reference,
                    'title' => $serviceRequest->title,
                    'customer' => $serviceRequest->customer?->name,
                    'professional' => $serviceRequest->providerProfile?->business_name,
                    'status' => $serviceRequest->status->value,
                    'status_label' => $serviceRequest->status->label(),
                    'status_tone' => $serviceRequest->status->tone(),
                    'created_at' => $serviceRequest->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'recentBookings' => ProviderBooking::query()
                ->with(['customer:id,name', 'providerProfile:id,business_name'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (ProviderBooking $booking) => [
                    'id' => $booking->id,
                    'reference' => $booking->reference,
                    'customer' => $booking->customer?->name,
                    'professional' => $booking->providerProfile?->business_name,
                    'starts_at' => $booking->starts_at?->toIso8601String(),
                    'amount' => (float) $booking->amount,
                    'status' => $booking->status->value,
                    'status_label' => $booking->status->label(),
                    'status_tone' => $booking->status->tone(),
                ])
                ->values()
                ->all(),
            'recentPayments' => Payment::query()
                ->with(['providerProfile:id,business_name', 'business:id,name'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (Payment $payment) => [
                    'id' => $payment->id,
                    'reference' => $payment->reference,
                    'owner' => $payment->ownerLabel(),
                    'professional' => $payment->providerProfile?->business_name,
                    'business' => $payment->business?->name,
                    'amount' => (float) $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status->value,
                    'status_label' => $payment->status->label(),
                    'status_tone' => $payment->status->tone(),
                    'paid_at' => $payment->paid_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'activity' => AdminAuditLog::query()
                ->with('actor:id,name')
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (AdminAuditLog $log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'action_label' => $log->actionLabel(),
                    'description' => $log->description,
                    'actor' => $log->actor?->name,
                    'created_at' => $log->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ]);
    }
}
