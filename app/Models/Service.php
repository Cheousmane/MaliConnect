<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'professional_id',
        'provider_profile_id',
        'name',
        'description',
        'price_min',
        'price_max',
        'price_type',
        'duration_minutes',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_min' => 'decimal:0',
            'price_max' => 'decimal:0',
            'duration_minutes' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ─── Types de tarification ───────────────────────────────

    const PRICE_TYPE_FIXED = 'fixed';    // Prix fixe

    const PRICE_TYPE_FROM = 'from';     // À partir de

    const PRICE_TYPE_RANGE = 'range';    // Fourchette

    const PRICE_TYPE_QUOTE = 'quote';    // Sur devis

    const PRICE_TYPE_HOURLY = 'hourly';   // Tarif horaire

    const PRICE_TYPES = [
        self::PRICE_TYPE_FIXED,
        self::PRICE_TYPE_FROM,
        self::PRICE_TYPE_RANGE,
        self::PRICE_TYPE_QUOTE,
        self::PRICE_TYPE_HOURLY,
    ];

    // ─── Relations ────────────────────────────────────────────

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    /**
     * Profil professionnel du nouveau module porteur du service.
     */
    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    /**
     * Prestations tarifées qui reprennent ce service.
     */
    public function pricingItems(): HasMany
    {
        return $this->hasMany(PricingItem::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    // ─── Accessors ────────────────────────────────────────────

    public function getFormattedPriceAttribute(): string
    {
        return match ($this->price_type) {
            self::PRICE_TYPE_FIXED => number_format($this->price_min, 0, ',', ' ').' FCFA',
            self::PRICE_TYPE_FROM => 'À partir de '.number_format($this->price_min, 0, ',', ' ').' FCFA',
            self::PRICE_TYPE_RANGE => number_format($this->price_min, 0, ',', ' ').' – '.number_format($this->price_max, 0, ',', ' ').' FCFA',
            self::PRICE_TYPE_HOURLY => number_format($this->price_min, 0, ',', ' ').' FCFA/h',
            default => 'Sur devis',
        };
    }
}
