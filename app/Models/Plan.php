<?php

namespace App\Models;

use App\Enums\BillingCycle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Plan commercial configurable depuis l'administration.
 *
 * Les limites et fonctionnalités sont stockées ici : aucune valeur n'est
 * codée en dur côté serveur, l'administration peut les ajuster sans
 * redéploiement. La convention « 0 » signifie « illimité » pour les limites
 * de services et de fiches tarifaires.
 */
class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'price_monthly',
        'price_yearly',
        'currency',
        'max_services',
        'max_media',
        'max_profiles',
        'max_pricing_sheets',
        'has_advanced_stats',
        'has_gallery',
        'has_collaborators',
        'has_priority_visibility',
        'has_advanced_quotes',
        'has_bookings',
        'has_priority_support',
        'has_highlight_options',
        'has_profile_badge',
        'has_centralized_requests',
        'has_full_pricing_sheets',
        'has_simple_pricing_sheets',
        'trial_days',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:0',
            'price_yearly' => 'decimal:0',
            'max_services' => 'integer',
            'max_media' => 'integer',
            'max_profiles' => 'integer',
            'max_pricing_sheets' => 'integer',
            'has_advanced_stats' => 'boolean',
            'has_gallery' => 'boolean',
            'has_collaborators' => 'boolean',
            'has_priority_visibility' => 'boolean',
            'has_advanced_quotes' => 'boolean',
            'has_bookings' => 'boolean',
            'has_priority_support' => 'boolean',
            'has_highlight_options' => 'boolean',
            'has_profile_badge' => 'boolean',
            'has_centralized_requests' => 'boolean',
            'has_full_pricing_sheets' => 'boolean',
            'has_simple_pricing_sheets' => 'boolean',
            'trial_days' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ─── Scopes ───────────────────────────────────────────────

    /**
     * Limite aux formules publiées dans la grille tarifaire.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Ordonne les formules selon leur position commerciale.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    // ─── Tarifs ───────────────────────────────────────────────

    /**
     * Prix de la formule pour une périodicité donnée.
     */
    public function priceFor(BillingCycle|string $cycle): float
    {
        $cycle = $cycle instanceof BillingCycle ? $cycle : BillingCycle::from($cycle);

        return (float) ($cycle === BillingCycle::Yearly ? $this->price_yearly : $this->price_monthly);
    }

    /**
     * Prix formaté pour l'interface.
     */
    public function formattedPriceFor(BillingCycle|string $cycle): string
    {
        return number_format($this->priceFor($cycle), 0, ',', ' ').' '.$this->currency;
    }

    /**
     * Indique si la formule est gratuite.
     */
    public function isFree(): bool
    {
        return $this->code === 'free' || ($this->priceFor(BillingCycle::Monthly) <= 0 && $this->priceFor(BillingCycle::Yearly) <= 0);
    }

    // ─── Limites ──────────────────────────────────────────────

    public function isUnlimitedServices(): bool
    {
        return $this->max_services === 0;
    }

    public function isUnlimitedPricingSheets(): bool
    {
        return $this->max_pricing_sheets === 0;
    }

    /**
     * Indique si une limite est atteinte.
     */
    public function limitReached(string $limit, int $used): bool
    {
        $max = match ($limit) {
            'services' => $this->max_services,
            'profiles' => $this->max_profiles,
            'pricing_sheets' => $this->max_pricing_sheets,
            'media' => $this->max_media,
            default => 0,
        };

        return $max > 0 && $used >= $max;
    }

    // ─── Fonctionnalités ──────────────────────────────────────

    /**
     * Indique si la formule active une fonctionnalité nommée.
     */
    public function hasFeature(string $feature): bool
    {
        return (bool) match ($feature) {
            'advanced_stats' => $this->has_advanced_stats,
            'gallery' => $this->has_gallery,
            'collaborators' => $this->has_collaborators,
            'priority_visibility' => $this->has_priority_visibility,
            'advanced_quotes' => $this->has_advanced_quotes,
            'bookings' => $this->has_bookings,
            'priority_support' => $this->has_priority_support,
            'highlight_options' => $this->has_highlight_options,
            'profile_badge' => $this->has_profile_badge,
            'centralized_requests' => $this->has_centralized_requests,
            'full_pricing_sheets' => $this->has_full_pricing_sheets,
            'simple_pricing_sheets' => $this->has_simple_pricing_sheets,
            default => false,
        };
    }
}
