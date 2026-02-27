<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CommunicationThread extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace_id',
        'marketplace_store_id',
        'channel',
        'external_thread_id',
        'subject',
        'product_id',
        'product_sku',
        'product_name',
        'order_id',
        'external_order_id',
        'customer_name',
        'customer_external_id',
        'status',
        'priority_score',
        'due_at',
        'last_inbound_at',
        'last_outbound_at',
        'response_time_sec',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'last_inbound_at' => 'datetime',
            'last_outbound_at' => 'datetime',
            'meta' => 'array',
            'priority_score' => 'integer',
            'response_time_sec' => 'integer',
        ];
    }

    public function marketplace()
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function marketplaceStore()
    {
        return $this->belongsTo(MarketplaceStore::class, 'marketplace_store_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function messages()
    {
        return $this->hasMany(CommunicationMessage::class, 'thread_id');
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('marketplaceStore', function (Builder $storeQuery) use ($user) {
            $storeQuery->where('user_id', $user->id);
        });
    }
}

