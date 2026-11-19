<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Disponibilité ponctuelle d'un profil professionnel.
 */
class ProviderProfileAvailability extends Model
{
    protected $fillable = [
        'provider_profile_id',
        'date',
        'starts_at',
        'ends_at',
        'is_available',
        'label',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_available' => 'boolean',
        ];
    }

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }
}
