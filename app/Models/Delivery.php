<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $fillable = [
        'order_id', 'client_id', 'client_name', 'client_phone',
        'address', 'reference', 'driver_id', 'user_id', 'cash_register_id',
        'status', 'payment_method', 'delivery_fee', 'notes',
        'scheduled_at', 'delivered_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'delivered_at' => 'datetime',
        'delivery_fee' => 'decimal:2',
    ];

    /* ─── Status labels ─────────────────────────── */
    public static array $statusLabels = [
        'pending'   => 'Pendiente',
        'preparing' => 'Preparando',
        'on_way'    => 'En camino',
        'delivered' => 'Entregado',
        'cancelled' => 'Cancelado',
    ];

    public static array $statusColors = [
        'pending'   => '#f59e0b',
        'preparing' => '#3b82f6',
        'on_way'    => '#f97316',
        'delivered' => '#22c55e',
        'cancelled' => '#ef4444',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::$statusLabels[$this->status] ?? $this->status;
    }

    public function getTotalWithFeeAttribute(): float
    {
        return ($this->order->total ?? 0) + $this->delivery_fee;
    }

    /* ─── Relationships ─────────────────────────── */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function driver()
    {
        return $this->belongsTo(DeliveryDriver::class, 'driver_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }

    /* ─── Scopes ────────────────────────────────── */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['delivered', 'cancelled']);
    }
}
