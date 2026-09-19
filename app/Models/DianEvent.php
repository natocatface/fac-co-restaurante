<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Bitácora de eventos DIAN (envíos, consultas, respuestas).
 * Sirve como auditoría y soporte ante una eventual inspección.
 */
class DianEvent extends Model
{
    protected $table = 'dian_events';

    protected $fillable = [
        'documentable_type', 'documentable_id',
        'event_type', 'response_code', 'description',
        'request_payload', 'response_payload',
        'user_id',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Helper para registrar un evento desde cualquier servicio DIAN.
     */
    public static function log(
        Model $documentable,
        string $eventType,
        ?string $code = null,
        ?string $description = null,
        ?string $request = null,
        ?string $response = null
    ): self {
        return self::create([
            'documentable_type' => get_class($documentable),
            'documentable_id'   => $documentable->getKey(),
            'event_type'        => $eventType,
            'response_code'     => $code,
            'description'       => $description,
            'request_payload'   => $request,
            'response_payload'  => $response,
            'user_id'           => auth()->id(),
        ]);
    }
}
