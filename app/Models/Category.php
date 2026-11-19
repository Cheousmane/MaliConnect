<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    public function subcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class);
    }

    public function professionals(): HasMany
    {
        return $this->hasMany(Professional::class);
    }

    /**
     * Profils professionnels rattachés à la catégorie.
     */
    public function providerProfiles(): HasMany
    {
        return $this->hasMany(ProviderProfile::class);
    }

    /**
     * Fiches tarifaires classées dans la catégorie.
     */
    public function pricingSheets(): HasMany
    {
        return $this->hasMany(PricingSheet::class);
    }

    /**
     * Prestations tarifées classées dans la catégorie.
     */
    public function pricingItems(): HasMany
    {
        return $this->hasMany(PricingItem::class);
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
