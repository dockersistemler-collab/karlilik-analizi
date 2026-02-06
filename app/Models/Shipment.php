<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'marketplace_code',
        'provider_key',
        'carrier_name_raw',
        'carrier_name_normalized',
        'tracking_number',
        'status',
        'last_event_at',
        'last_polled_at',
        'last_error',
        'meta',
    ];

    protected $casts = [
        'last_event_at' => 'datetime',
        'last_polled_at' => 'datetime',
        'meta' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function events()
    {
        return $this->hasMany(ShipmentTrackingEvent::class);
    }
}
