<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Order extends Model
{
    protected $fillable = [
        'table_id',
        'user_id',
        'client_id',
        'status',
        'total',
        'payment_method',
        'received_amount',
        'change_amount',
        'document_type',
        'client_name',
        'client_document',
        'discount',
        'tip',
        'cash_register_id',

        // ── DIAN (Colombia) ────────────────────────────
        'dian_prefijo',
        'dian_numero',
        'dian_resolution_id',
        'subtotal',
        'iva',
        'ico',
        'descuento_total',
        'total_a_pagar',
        'client_tipo_documento',
        'client_dv',
        'client_email',
        'client_phone',
        'client_address',
        'client_city_code',
        'client_dept_code',
        'dian_status',
        'cufe',
        'dian_zip_id',
        'dian_response_code',
        'dian_description',
        'dian_errors',
        'xml_path',
        'ar_path',
        'pdf_path',
        'qr_url',
        'sent_at',
        'accepted_at',
    ];

    protected $casts = [
        'sent_at'         => 'datetime',
        'accepted_at'     => 'datetime',
        'dian_numero'     => 'integer',
        'subtotal'        => 'decimal:2',
        'iva'             => 'decimal:2',
        'ico'             => 'decimal:2',
        'descuento_total' => 'decimal:2',
        'total'           => 'decimal:2',
        'total_a_pagar'   => 'decimal:2',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function details()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function resolution()
    {
        return $this->belongsTo(DianResolution::class, 'dian_resolution_id');
    }

    public function creditNotes()
    {
        return $this->hasMany(DianCreditNote::class);
    }

    public function events(): MorphMany
    {
        return $this->morphMany(DianEvent::class, 'documentable');
    }

    /**
     * Número completo de factura DIAN: SETP-1, FE-153, etc.
     * Devuelve null si aún no se ha asignado.
     */
    public function getFullNumberAttribute(): ?string
    {
        if (!$this->dian_prefijo && !$this->dian_numero) {
            return null;
        }
        return trim(($this->dian_prefijo ?? '') . ($this->dian_numero ?? ''));
    }

    /**
     * ¿Esta orden debe emitir Factura Electrónica DIAN?
     */
    public function isElectronic(): bool
    {
        return $this->document_type === 'Factura';
    }
}
