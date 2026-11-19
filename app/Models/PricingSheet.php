<?php

namespace App\Models;

use App\Enums\PricingSheetStatus;
use Database\Factories\PricingSheetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Fiche tarifaire d'un profil professionnel.
 *
 * Une fiche regroupe des prestations et suit un cycle de vie simple :
 * brouillon, publiée puis éventuellement archivée. Seules les fiches
 * publiées, non expirées et rattachées à un profil public sont visibles.
 */
class PricingSheet extends Model
{
    /** @use HasFactory<PricingSheetFactory> */
    use HasFactory;

    protected $fillable = [
        'provider_profile_id',
        'category_id',
        'name',
        'description',
        'currency',
        'general_conditions',
        'status',
        'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'status' => PricingSheetStatus::class,
            'valid_until' => 'date',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    /**
     * Profil professionnel propriétaire de la fiche.
     */
    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    /**
     * Catégorie principale de la fiche.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Prestations tarifées, dans l'ordre défini par le professionnel.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PricingItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Prestations actuellement proposées.
     */
    public function availableItems(): HasMany
    {
        return $this->hasMany(PricingItem::class)->where('is_available', true);
    }

    // ─── Helpers ──────────────────────────────────────────────

    /**
     * Indique si la fiche est publiée.
     */
    public function isPublished(): bool
    {
        return $this->status === PricingSheetStatus::Published;
    }

    /**
     * Indique si la fiche est un brouillon.
     */
    public function isDraft(): bool
    {
        return $this->status === PricingSheetStatus::Draft;
    }

    /**
     * Indique si la fiche est archivée.
     */
    public function isArchived(): bool
    {
        return $this->status === PricingSheetStatus::Archived;
    }

    /**
     * Indique si la date de validité est dépassée.
     *
     * Une fiche reste valable jusqu'à la fin de la journée indiquée.
     */
    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->lt(today());
    }

    /**
     * Indique si la fiche peut être présentée sur le profil public.
     */
    public function isPubliclyVisible(): bool
    {
        if (! $this->isPublished() || $this->isExpired()) {
            return false;
        }

        return $this->providerProfile?->isPubliclyVisible() ?? false;
    }

    /**
     * Libellé du statut affiché dans l'interface.
     */
    public function statusLabel(): string
    {
        return $this->status->label();
    }

    /**
     * Duplique la fiche et ses prestations en brouillon.
     */
    public function duplicate(): self
    {
        return DB::transaction(function () {
            $copy = $this->replicate(['public_token', 'published_at', 'archived_at']);

            $copy->name = Str::limit('Copie de '.$this->name, 150, '');
            $copy->public_token = (string) Str::uuid();
            $copy->status = PricingSheetStatus::Draft;
            $copy->published_at = null;
            $copy->archived_at = null;
            $copy->save();

            foreach ($this->items()->get() as $item) {
                $itemCopy = $item->replicate();
                $itemCopy->pricing_sheet_id = $copy->id;
                $itemCopy->save();
            }

            return $copy;
        });
    }

    // ─── Scopes ───────────────────────────────────────────────

    /**
     * Limite aux fiches publiées.
     */
    public function scopePublished($query)
    {
        return $query->where('status', PricingSheetStatus::Published);
    }

    /**
     * Limite aux fiches visibles publiquement : publiées et non expirées.
     */
    public function scopePubliclyVisible($query)
    {
        return $query->where('status', PricingSheetStatus::Published)
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhereDate('valid_until', '>=', today());
            });
    }

    // ─── Boot ─────────────────────────────────────────────────

    /**
     * Génère le jeton public à la création.
     */
    protected static function booted(): void
    {
        static::creating(function (self $sheet) {
            if (blank($sheet->public_token)) {
                $sheet->public_token = (string) Str::uuid();
            }
        });
    }
}
