<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DianCreditNote extends Model
{
    protected $table = 'dian_credit_notes';

    protected $fillable = [
        'order_id', 'prefijo', 'numero', 'dian_resolution_id',
        'reason_code', 'reason_description',
        'subtotal', 'iva', 'total',
        'dian_status', 'cude', 'dian_zip_id', 'dian_response_code',
        'dian_description', 'dian_errors',
        'xml_path', 'ar_path', 'pdf_path', 'qr_url',
        'sent_at', 'accepted_at', 'user_id',
    ];

    protected $casts = [
        'numero'      => 'integer',
        'reason_code' => 'integer',
        'subtotal'    => 'decimal:2',
        'iva'         => 'decimal:2',
        'total'       => 'decimal:2',
        'sent_at'     => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function resolution(): BelongsTo
    {
        return $this->belongsTo(DianResolution::class, 'dian_resolution_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): MorphMany
    {
        return $this->morphMany(DianEvent::class, 'documentable');
    }

    public function getFullNumberAttribute(): string
    {
        return $this->prefijo . '-' . $this->numero;
    }
}
