<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BillingSubscriptionEvent extends Model
{
    use HasFactory;

    protected $table = 'billing_subscription_events';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'subscription_id',
        'provider_event_id',
        'event_type',
        'event_hash',
        'payload',
        'received_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'received_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}
