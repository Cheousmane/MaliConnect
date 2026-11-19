<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReportStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\SubscriptionStatus;
use App\Models\AdminAuditLog;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\Plan;
use App\Models\ProviderBooking;
use App\Models\Report;
use App\Models\ServiceRequest;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RoleSeeder;

/**
 * Vérifie les opérations administratives sur les demandes, réservations,
 * abonnements, paiements et signalements.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

/**
 * Crée un administrateur de test.
 */
function operationsAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

test('un administrateur change le statut d’une demande', function () {
    $admin = operationsAdmin();
    $serviceRequest = ServiceRequest::factory()->create();

    $this->actingAs($admin)
        ->patch("/admin/demandes/{$serviceRequest->id}/statut", ['status' => ServiceRequestStatus::Completed->value])
        ->assertSessionHasNoErrors();

    $serviceRequest->refresh();

    expect($serviceRequest->status)->toBe(ServiceRequestStatus::Completed);
    expect($serviceRequest->closed_at)->not->toBeNull();
    expect(AdminAuditLog::query()->where('action', 'service_request.status.updated')->count())->toBe(1);
});

test('un administrateur change le statut d’une réservation', function () {
    $admin = operationsAdmin();
    $booking = ProviderBooking::factory()->create();

    $this->actingAs($admin)
        ->patch("/admin/reservations/{$booking->id}/statut", ['status' => BookingStatus::Confirmed->value])
        ->assertSessionHasNoErrors();

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::Confirmed);
    expect($booking->confirmed_at)->not->toBeNull();
    expect(AdminAuditLog::query()->where('action', 'booking.status.updated')->count())->toBe(1);
});

test('un administrateur suspend un abonnement', function () {
    $admin = operationsAdmin();
    $subscription = Subscription::factory()->active()->create();

    $this->actingAs($admin)
        ->patch("/admin/abonnements/{$subscription->id}/suspension", ['reason' => 'Test de suspension'])
        ->assertSessionHasNoErrors();

    $subscription->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Suspended);
    expect(AdminAuditLog::query()->where('action', 'subscription.suspended')->count())->toBe(1);
});

test('un administrateur valide un paiement', function () {
    $admin = operationsAdmin();
    $this->seed(PlanSeeder::class);
    $plan = Plan::query()->where('code', 'pro')->firstOrFail();
    $payment = Payment::factory()->pending()->forPlan($plan)->create(['amount' => 12000]);
    PaymentProof::factory()->create(['payment_id' => $payment->id]);

    $this->actingAs($admin)
        ->post("/admin/paiements/{$payment->id}/validation", ['note' => 'Validation test'])
        ->assertSessionHasNoErrors();

    $payment->refresh();

    expect($payment->status)->toBe(PaymentStatus::Paid);
    expect($payment->paid_at)->not->toBeNull();
});

test('un administrateur traite un signalement', function () {
    $admin = operationsAdmin();
    $report = Report::factory()->pending()->create();

    $this->actingAs($admin)
        ->patch("/admin/signalements/{$report->id}/statut", [
            'status' => ReportStatus::Resolved->value,
            'resolution_note' => 'Le professionnel a été contacté.',
        ])
        ->assertSessionHasNoErrors();

    $report->refresh();

    expect($report->status)->toBe(ReportStatus::Resolved);
    expect($report->handled_by)->toBe($admin->id);
    expect($report->handled_at)->not->toBeNull();
    expect(AdminAuditLog::query()->where('action', 'report.status.updated')->count())->toBe(1);
});

test('un professionnel ne peut pas accéder aux modules sensibles', function () {
    $professional = User::factory()->create();
    $professional->assignRole('professional');

    $this->actingAs($professional)->get('/admin/demandes')->assertForbidden();
    $this->actingAs($professional)->get('/admin/paiements')->assertForbidden();
    $this->actingAs($professional)->get('/admin/journal')->assertForbidden();
});
