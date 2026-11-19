<?php

namespace App\Models;

use App\Enums\PricingType;
use Database\Factories\PricingItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Prestation tarifée d'une fiche.
 *
 * Les montants sont facultatifs selon le mode de tarification. Un prix
 * absent n'est jamais transformé en zéro : l'interface affiche « Sur devis »,
 * « Sur diagnostic » ou « Prix non communiqué ».
 */
class PricingItem extends Model
{
    /** @use HasFactory<PricingItemFactory> */
    use HasFactory;

    protected $fillable = [
        'pricing_sheet_id',
        'service_id',
        'category_id',
        'name',
        'description',
        'pricing_type',
        'price',
        'price_min',
        'price_max',
        'billing_unit',
        'conditions',
        'estimated_duration_minutes',
        'is_available',
        'requires_diagnostic',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'pricing_type' => PricingType::class,
            'price' => 'decimal:0',
            'price_min' => 'decimal:0',
            'price_max' => 'decimal:0',
            'estimated_duration_minutes' => 'integer',
            'is_available' => 'boolean',
            'requires_diagnostic' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    /**
     * Fiche tarifaire porteuse.
     */
    public function pricingSheet(): BelongsTo
    {
        return $this->belongsTo(PricingSheet::class);
    }

    /**
     * Service existant associé, si la prestation en reprend le libellé.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Catégorie spécifique de la prestation.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // ─── Helpers ──────────────────────────────────────────────

    /**
     * Indique si au moins un montant a été renseigné.
     */
    public function hasPrice(): bool
    {
        return $this->price !== null || $this->price_min !== null || $this->price_max !== null;
    }

    /**
     * Indique si la prestation dépend d'un diagnostic.
     */
    public function requiresDiagnostic(): bool
    {
        return $this->requires_diagnostic;
    }

    /**
     * Indique si la prestation est proposée sur devis.
     */
    public function isQuote(): bool
    {
        return $this->pricing_type->isQuote();
    }

    /**
     * Symbole de la devise de la fiche.
     */
    public function currencyLabel(): string
    {
        $currency = $this->pricingSheet?->currency ?? 'XOF';

        return $currency === 'XOF' ? 'FCFA' : $currency;
    }

    /**
     * Prix formaté, sans jamais inventer de montant.
     */
    public function getFormattedPriceAttribute(): string
    {
        $currency = $this->currencyLabel();

        if (! $this->hasPrice()) {
            if ($this->requiresDiagnostic()) {
                return 'Sur diagnostic';
            }

            return $this->isQuote() ? 'Sur devis' : 'Prix non communiqué';
        }

        $amount = $this->formattedAmount($currency);

        $suffix = $this->pricing_type->unitSuffix();

        if ($suffix !== null) {
            $amount .= ' '.$suffix;
        }

        if ($this->isQuote()) {
            $amount .= ' · sur devis';
        }

        return $amount;
    }

    /**
     * Construit le montant à partir du prix fixe ou de la fourchette.
     */
    private function formattedAmount(string $currency): string
    {
        $format = fn (mixed $value): string => number_format((float) $value, 0, ',', ' ').' '.$currency;

        if ($this->price !== null) {
            return $format($this->price);
        }

        if ($this->price_min !== null && $this->price_max !== null) {
            if ((float) $this->price_min === (float) $this->price_max) {
                return $format($this->price_min);
            }

            return 'De '.$format($this->price_min).' à '.$format($this->price_max);
        }

        if ($this->price_min !== null) {
            return 'À partir de '.$format($this->price_min);
        }

        return "Jusqu'à ".$format($this->price_max);
    }

    /**
     * Durée estimée formatée pour l'affichage.
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if ($this->estimated_duration_minutes === null) {
            return null;
        }

        if ($this->estimated_duration_minutes < 60) {
            return $this->estimated_duration_minutes.' min';
        }

        $hours = intdiv($this->estimated_duration_minutes, 60);
        $minutes = $this->estimated_duration_minutes % 60;

        return $minutes === 0 ? $hours.' h' : $hours.' h '.$minutes.' min';
    }

    // ─── Scopes ───────────────────────────────────────────────

    /**
     * Ordonne les prestations selon le choix du professionnel.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
