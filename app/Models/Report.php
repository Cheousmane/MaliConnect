<?php

namespace App\Models;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Signalement d'une ressource par un utilisateur.
 */
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    protected $fillable = [
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'description',
        'status',
        'handled_by',
        'handled_at',
        'resolution_note',
    ];

    protected function casts(): array
    {
        return [
            'reason' => ReportReason::class,
            'status' => ReportStatus::class,
            'handled_at' => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────

    /**
     * Compte à l'origine du signalement.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Administrateur ayant traité le signalement.
     */
    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /**
     * Ressource signalée.
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    // ─── Scopes ───────────────────────────────────────────────

    /**
     * Limite aux signalements encore ouverts.
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', [ReportStatus::Pending, ReportStatus::Reviewed]);
    }

    // ─── Helpers ──────────────────────────────────────────────

    /**
     * Indique si le signalement attend encore une décision.
     */
    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }
}
