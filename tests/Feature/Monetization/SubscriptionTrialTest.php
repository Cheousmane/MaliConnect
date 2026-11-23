<?php

use App\Enums\BillingCycle;
use App\Enums\PaymentMethod;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\ProviderProfile;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Monetization\SubscriptionService;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PlanSeeder::class);
});

/**
 * Crée un professionnel et son profil indépendant.
 *
 * @return array{0: User, 1: ProviderProfile}
 */
function trialOwner(): array
{
    $user = User::factory()->create();
    $user->assignRole('professional');
    $profile = ProviderProfile::factory()->forUser($user)->verified()->create();

    return [$user, $profile];
}

test('un profil sans abonnement est éligible à l’essai gratuit', function () {
    [, $profile] = trialOwner();

    $service = app(SubscriptionService::class);
    $plan = Plan::query()->where('code', 'pro')->firstOrFail();

    expect($service->isTrialAvailable($profile))->toBeTrue()
        ->and($service->canStartTrial($profile, $plan))->toBeTrue();
});

test('un abonnement FREE automatique reste éligible à l’essai gratuit', function () {
    [$user, $profile] = trialOwner();

    $service = app(SubscriptionService::class);
    $service->ensureFreeSubscription($profile, $user);

    $plan = Plan::query()->where('code', 'pro')->firstOrFail();

    expect($service->isTrialAvailable($profile))->toBeTrue()
        ->and($service->canStartTrial($profile, $plan))->toBeTrue();
});

test('une souscription depuis FREE démarre l’essai sans paiement ni preuve', function () {
    [$user, $profile] = trialOwner();

    $service = app(SubscriptionService::class);
    $service->ensureFreeSubscription($profile, $user);

    $plan = Plan::query()->where('code', 'pro')->firstOrFail();

    $this->actingAs($user)
        ->post(route('pro.subscription.subscribe'), [
            'owner_type' => 'profile',
            'owner_id' => $profile->id,
            'plan_id' => $plan->id,
            'billing_cycle' => BillingCycle::Monthly->value,
            'method' => PaymentMethod::OrangeMoney->value,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $subscription = Subscription::query()
        ->where('provider_profile_id', $profile->id)
        ->firstOrFail();

    expect($subscription->status)->toBe(SubscriptionStatus::Trialing)
        ->and($subscription->plan)->toBe('pro')
        ->and($subscription->trial_ends_at)->not->toBeNull()
        ->and($subscription->payments()->count())->toBe(0);

    expect($subscription->histories()->where('event', 'trial_started')->count())->toBe(1);
});

test('un essai déjà consommé exige une preuve de paiement', function () {
    [$user, $profile] = trialOwner();

    $service = app(SubscriptionService::class);
    $service->ensureFreeSubscription($profile, $user);

    $pro = Plan::query()->where('code', 'pro')->firstOrFail();
    $business = Plan::query()->where('code', 'business')->firstOrFail();

    // Premier essai consommé sur la formule PRO.
    $this->actingAs($user)->post(route('pro.subscription.subscribe'), [
        'owner_type' => 'profile',
        'owner_id' => $profile->id,
        'plan_id' => $pro->id,
        'billing_cycle' => BillingCycle::Monthly->value,
        'method' => PaymentMethod::OrangeMoney->value,
    ])->assertSessionHasNoErrors();

    expect($service->isTrialAvailable($profile))->toBeFalse()
        ->and($service->canStartTrial($profile, $business))->toBeFalse();

    $this->actingAs($user)
        ->post(route('pro.subscription.subscribe'), [
            'owner_type' => 'profile',
            'owner_id' => $profile->id,
            'plan_id' => $business->id,
            'billing_cycle' => BillingCycle::Monthly->value,
            'method' => PaymentMethod::OrangeMoney->value,
        ])
        ->assertSessionHasErrors('proof');

    expect(Subscription::query()->where('provider_profile_id', $profile->id)->firstOrFail()->plan)
        ->toBe('pro');
});

test('un abonnement payant actif n’ouvre pas droit à un nouvel essai', function () {
    [$user, $profile] = trialOwner();

    $plan = Plan::query()->where('code', 'pro')->firstOrFail();

    Subscription::factory()->create([
        'provider_profile_id' => $profile->id,
        'plan_id' => $plan->id,
        'plan' => $plan->code,
        'status' => SubscriptionStatus::Active,
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
        'amount' => $plan->price_monthly,
        'currency' => $plan->currency,
    ]);

    $service = app(SubscriptionService::class);

    expect($service->isTrialAvailable($profile))->toBeFalse()
        ->and($service->canStartTrial($profile, $plan))->toBeFalse();
});

test('la page des offres signale les formules éligibles à l’essai', function () {
    [$user, $profile] = trialOwner();

    $service = app(SubscriptionService::class);
    $service->ensureFreeSubscription($profile, $user);

    $this->actingAs($user)
        ->get(route('pro.subscription.plans', [
            'owner_type' => 'profile',
            'owner_id' => $profile->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('pro/Plans')
            ->where('plans.0.code', 'free')
            ->where('plans.0.trial_eligible', false)
            ->where('plans.1.code', 'pro')
            ->where('plans.1.trial_eligible', true)
            ->where('plans.2.code', 'business')
            ->where('plans.2.trial_eligible', true)
        );
});
