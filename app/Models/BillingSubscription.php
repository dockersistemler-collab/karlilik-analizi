<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BillingSubscription extends Model
{
    use HasFactory;

    protected $table = 'billing_subscriptions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'provider',
        'plan_code',
        'status',
        'iyzico_subscription_reference_code',
        'iyzico_customer_reference_code',
        'iyzico_pricing_plan_reference_code',
        'iyzico_checkout_form_token',
        'iyzico_checkout_form_content',
        'started_at',
        'canceled_at',
        'last_payment_at',
        'next_payment_at',
        'past_due_since',
        'grace_until',
        'last_dunning_sent_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'canceled_at' => 'datetime',
        'last_payment_at' => 'datetime',
        'next_payment_at' => 'datetime',
        'past_due_since' => 'datetime',
        'grace_until' => 'datetime',
        'last_dunning_sent_at' => 'datetime',
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
