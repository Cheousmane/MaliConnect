<?php

namespace App\Models;

use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Média associé à un profil professionnel.
 */
class ProviderProfileMedia extends Model
{
    protected $fillable = [
        'provider_profile_id',
        'type',
        'path',
        'disk',
        'caption',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'position' => 'integer',
        ];
    }

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    /**
     * Adresse publique du média.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
