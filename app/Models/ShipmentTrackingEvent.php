<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentTrackingEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'shipment_id',
        'provider_key',
        'event_code',
        'description',
        'location',
        'occurred_at',
        'payload_json',
        'hash',
        'created_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'payload_json' => 'array',
        'created_at' => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}
