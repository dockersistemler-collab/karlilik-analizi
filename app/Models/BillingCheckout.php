<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class BillingCheckout extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id',
        'billing_subscription_id',
        'plan_code',
        'purpose',
        'status',
        'provider',
        'provider_session_id',
        'checkout_form_content',
        'provider_token',
        'raw_initialize',
        'raw_callback',
        'raw_webhook',
        'completed_at',
    ];

    protected $casts = [
        'raw_initialize' => 'array',
        'raw_callback' => 'array',
        'raw_webhook' => 'array',
        'completed_at' => 'datetime',
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
