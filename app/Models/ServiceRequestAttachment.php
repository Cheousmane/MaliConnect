<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pièce jointe (photo ou document) d'une demande de devis.
 */
class ServiceRequestAttachment extends Model
{
    protected $fillable = [
        'service_request_id',
        'path',
        'disk',
        'name',
        'type',
        'mime_type',
        'size',
    ];

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    /**
     * Adresse protégée de la pièce jointe.
     *
     * Le fichier n'est jamais exposé directement : la route vérifie que
     * l'utilisateur a accès à la demande avant de le télécharger.
     */
    public function getUrlAttribute(): string
    {
        return route('requests.attachments.download', [
            'serviceRequest' => $this->service_request_id,
            'attachment' => $this->id,
        ]);
    }
}
