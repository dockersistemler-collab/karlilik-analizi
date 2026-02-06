<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEndpoint extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'url',
        'secret',
        'events',
        'is_active',
        'disabled_at',
        'disabled_reason',
        'rotated_at',
        'headers_json',
        'timeout_seconds',
    ];

    protected $casts = [
        'secret' => 'encrypted',
        'events' => 'array',
        'headers_json' => 'array',
        'is_active' => 'boolean',
        'timeout_seconds' => 'integer',
        'disabled_at' => 'datetime',
        'rotated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deliveries()
    {
        return $this->hasMany(WebhookDelivery::class);
    }
}
