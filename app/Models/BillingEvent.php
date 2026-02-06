<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'subscription_id',
        'invoice_id',
        'type',
        'status',
        'amount',
        'currency',
        'provider',
        'correlation_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'amount' => 'decimal:2',
    ];

    public function actions(): HasMany
    {
        return $this->hasMany(BillingEventAction::class);
    }
}
