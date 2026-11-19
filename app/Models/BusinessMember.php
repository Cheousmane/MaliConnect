<?php

namespace App\Models;

use App\Enums\BusinessMemberRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Collaboration entre un utilisateur et une entreprise.
 */
class BusinessMember extends Model
{
    protected $fillable = [
        'business_id',
        'user_id',
        'role',
        'status',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => BusinessMemberRole::class,
            'joined_at' => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Helpers ──────────────────────────────────────────────

    /**
     * Indique si le collaborateur peut gérer les profils de l'entreprise.
     */
    public function canManageProfiles(): bool
    {
        return $this->role->canManageProfiles();
    }

    /**
     * Indique si le collaborateur peut gérer les autres collaborateurs.
     */
    public function canManageMembers(): bool
    {
        return $this->role->canManageMembers();
    }
}
