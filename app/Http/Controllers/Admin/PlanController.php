<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePlanRequest;
use App\Http\Requests\Admin\UpdatePlanActivationRequest;
use App\Http\Requests\Admin\UpdatePlanRequest;
use App\Models\Plan;
use App\Services\Admin\AuditLogger;
use App\Services\Monetization\EntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Administration de la grille commerciale.
 *
 * Les prix, limites et fonctionnalités sont modifiables sans redéploiement.
 * Chaque modification est journalisée pour conserver la traçabilité.
 */
class PlanController extends Controller
{
    public function __construct(private readonly EntitlementService $entitlements) {}

    /**
     * Affiche les formules et leurs indicateurs d'usage.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Plan::class);

        $plans = Plan::query()
            ->ordered()
            ->withCount([
                'subscriptions',
                'subscriptions as active_subscriptions_count' => fn ($query) => $query->active(),
            ])
            ->get()
            ->map(fn (Plan $plan) => [
                ...$this->entitlements->planPayload($plan),
                'is_active' => $plan->is_active,
                'sort_order' => $plan->sort_order,
                'trial_days' => $plan->trial_days,
                'subscriptions_count' => $plan->subscriptions_count,
                'active_subscriptions_count' => $plan->active_subscriptions_count,
                'features' => $this->entitlements->featuresForPlan($plan),
                'raw' => [
                    'has_advanced_stats' => $plan->has_advanced_stats,
                    'has_gallery' => $plan->has_gallery,
                    'has_collaborators' => $plan->has_collaborators,
                    'has_priority_visibility' => $plan->has_priority_visibility,
                    'has_advanced_quotes' => $plan->has_advanced_quotes,
                    'has_bookings' => $plan->has_bookings,
                    'has_priority_support' => $plan->has_priority_support,
                    'has_highlight_options' => $plan->has_highlight_options,
                    'has_profile_badge' => $plan->has_profile_badge,
                    'has_centralized_requests' => $plan->has_centralized_requests,
                    'has_full_pricing_sheets' => $plan->has_full_pricing_sheets,
                    'has_simple_pricing_sheets' => $plan->has_simple_pricing_sheets,
                ],
            ])
            ->values()
            ->all();

        return Inertia::render('admin/Plans', [
            'plans' => $plans,
            'featureOptions' => collect($this->entitlements->featuresForPlan(new Plan))
                ->map(fn (array $feature) => [
                    'key' => $feature['key'],
                    'label' => $feature['label'],
                ])
                ->values()
                ->all(),
        ]);
    }

    /**
     * Crée une nouvelle formule.
     */
    public function store(StorePlanRequest $request, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('manage', Plan::class);

        $validated = $request->validated();
        $validated['code'] = Str::slug($validated['code'], '_');
        $validated['currency'] = Str::upper($validated['currency']);

        $plan = Plan::create($validated);

        $audit->log(
            'plan.created',
            sprintf('Formule « %s » créée.', $plan->name),
            $plan,
            [
                'code' => $plan->code,
                'price_monthly' => (float) $plan->price_monthly,
                'price_yearly' => (float) $plan->price_yearly,
                'max_services' => $plan->max_services,
                'max_profiles' => $plan->max_profiles,
            ],
            $request->user(),
            $request,
        );

        return back()->with('success', 'La formule a été créée.');
    }

    /**
     * Met à jour une formule existante.
     */
    public function update(UpdatePlanRequest $request, Plan $plan, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('manage', $plan);

        $validated = $request->validated();
        $validated['currency'] = Str::upper($validated['currency']);

        $previous = [
            'price_monthly' => (float) $plan->price_monthly,
            'price_yearly' => (float) $plan->price_yearly,
            'max_services' => $plan->max_services,
            'max_profiles' => $plan->max_profiles,
            'max_pricing_sheets' => $plan->max_pricing_sheets,
            'is_active' => $plan->is_active,
        ];

        $plan->update($validated);

        $audit->log(
            'plan.updated',
            sprintf('Formule « %s » mise à jour.', $plan->name),
            $plan,
            [
                'previous' => $previous,
                'current' => [
                    'price_monthly' => (float) $plan->price_monthly,
                    'price_yearly' => (float) $plan->price_yearly,
                    'max_services' => $plan->max_services,
                    'max_profiles' => $plan->max_profiles,
                    'max_pricing_sheets' => $plan->max_pricing_sheets,
                    'is_active' => $plan->is_active,
                ],
            ],
            $request->user(),
            $request,
        );

        return back()->with('success', 'La formule a été mise à jour.');
    }

    /**
     * Active ou retire une formule de la grille publique.
     */
    public function updateActivation(
        UpdatePlanActivationRequest $request,
        Plan $plan,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->authorize('manage', $plan);

        $isActive = $request->boolean('is_active');

        // Une grille commerciale vide empêcherait toute nouvelle souscription.
        if (! $isActive && Plan::query()->where('is_active', true)->whereKeyNot($plan->id)->doesntExist()) {
            return back()->with('error', 'Au moins une formule doit rester active.');
        }

        $plan->forceFill(['is_active' => $isActive])->save();

        $audit->log(
            'plan.activation.updated',
            sprintf('Formule « %s » %s.', $plan->name, $isActive ? 'activée' : 'désactivée'),
            $plan,
            ['is_active' => $isActive],
            $request->user(),
            $request,
        );

        return back()->with('success', $isActive ? 'La formule est de nouveau proposée.' : 'La formule a été retirée de la grille.');
    }
}
