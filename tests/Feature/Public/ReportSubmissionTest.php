<?php

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\ProviderProfile;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * Vérifie le dépôt d'un signalement depuis la fiche publique.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

/**
 * Crée un client authentifié.
 */
function reportCustomer(): User
{
    $user = User::factory()->create();
    $user->assignRole('customer');

    return $user;
}

test('un visiteur doit se connecter pour signaler un profil', function () {
    $profile = ProviderProfile::factory()->verified()->create();

    $this->post("/professionnels/{$profile->slug}/signaler", [
        'reason' => ReportReason::Fraud->value,
    ])->assertRedirect('/login');
});

test('un client peut signaler un profil vérifié', function () {
    $customer = reportCustomer();
    $profile = ProviderProfile::factory()->verified()->create();

    $this->actingAs($customer)
        ->post("/professionnels/{$profile->slug}/signaler", [
            'reason' => ReportReason::Fraud->value,
            'description' => 'Le prestataire demande un paiement hors plateforme.',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $report = Report::query()->firstOrFail();

    expect($report->reporter_id)->toBe($customer->id);
    expect($report->reportable_id)->toBe($profile->id);
    expect($report->reason)->toBe(ReportReason::Fraud);
    expect($report->status)->toBe(ReportStatus::Pending);
});

test('un second signalement ouvert sur le même profil est refusé', function () {
    $customer = reportCustomer();
    $profile = ProviderProfile::factory()->verified()->create();

    Report::factory()->pending()->create([
        'reporter_id' => $customer->id,
        'reportable_type' => $profile->getMorphClass(),
        'reportable_id' => $profile->id,
    ]);

    $this->actingAs($customer)
        ->post("/professionnels/{$profile->slug}/signaler", [
            'reason' => ReportReason::Spam->value,
        ])
        ->assertSessionHas('error');

    expect(Report::query()->count())->toBe(1);
});

test('un motif inconnu est rejeté', function () {
    $customer = reportCustomer();
    $profile = ProviderProfile::factory()->verified()->create();

    $this->actingAs($customer)
        ->post("/professionnels/{$profile->slug}/signaler", [
            'reason' => 'inconnu',
        ])
        ->assertSessionHasErrors('reason');

    expect(Report::query()->count())->toBe(0);
});

test('un profil non vérifié ne peut pas être signalé publiquement', function () {
    $customer = reportCustomer();
    $profile = ProviderProfile::factory()->pending()->create();

    $this->actingAs($customer)
        ->post("/professionnels/{$profile->slug}/signaler", [
            'reason' => ReportReason::Fraud->value,
        ])
        ->assertNotFound();
});
