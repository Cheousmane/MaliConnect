<?php

use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\Plan;
use App\Models\ProviderProfile;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Monetization\EntitlementService;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PlanSeeder::class);
});

test('un profil sans abonnement est limité au plan FREE', function () {
    $user = User::factory()->create();
    $user->assignRole('professional');
    $profile = ProviderProfile::factory()->verified()->create(['user_id' => $user->id]);

    $service = app(EntitlementService::class);

    expect($service->planFor($profile)->code)->toBe('free');
    expect($service->canManageCollaborators($profile))->toBeFalse();
    expect($service->hasAdvancedStats($profile))->toBeFalse();
});

test('un abonnement PRO débloque la galerie et les statistiques', function () {
    $user = User::factory()->create();
    $user->assignRole('professional');
    $profile = ProviderProfile::factory()->verified()->create(['user_id' => $user->id]);
    $business = Business::factory()->create();
    $business->owner()->associate($user);
    $business->save();
    $plan = Plan::where('code', 'pro')->firstOrFail();

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

    $service = app(EntitlementService::class);

    expect($service->planFor($profile)->code)->toBe('pro');
    expect($service->canUploadMedia($profile))->toBeTrue();
    expect($service->hasAdvancedStats($profile))->toBeTrue();
    expect($service->canManageCollaborators($profile))->toBeFalse();
});

test('un abonnement BUSINESS permet les collaborateurs et services illimités', function () {
    $user = User::factory()->create();
    $user->assignRole('professional');
    $business = Business::factory()->create();
    $business->owner()->associate($user);
    $business->save();
    $plan = Plan::where('code', 'business')->firstOrFail();

    Subscription::factory()->create([
        'provider_profile_id' => null,
        'business_id' => $business->id,
        'plan_id' => $plan->id,
        'plan' => $plan->code,
        'status' => SubscriptionStatus::Active,
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
        'amount' => $plan->price_monthly,
        'currency' => $plan->currency,
    ]);

    $service = app(EntitlementService::class);

    expect($service->planForBusiness($business)->code)->toBe('business');
    expect($service->canManageCollaboratorsForBusiness($business))->toBeTrue();
    expect($service->planForBusiness($business)->max_services)->toBe(50);
});
