<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'starts_at',
        'ends_at',
        'cancelled_at',
        'amount',
        'billing_period',
        'auto_renew',
        'current_products_count',
        'current_marketplaces_count',
        'current_month_orders_count',
        'usage_reset_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'usage_reset_at' => 'datetime',
        'amount' => 'decimal:2',
        'auto_renew' => 'boolean',
    ];

    // İlişkiler
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    // Helper metodlar
    public function isActive()
    {
        return $this->status === 'active' && $this->ends_at->isFuture();
    }

    public function isExpired()
    {
        return $this->ends_at->isPast();
    }

    public function daysRemaining()
    {
        return $this->ends_at->diffInDays(now());
    }

    // Limit kontrolleri
    public function canAddProduct()
    {
        if ($this->plan->hasUnlimitedProducts()) {
            return true;
        }
        return $this->current_products_count < $this->plan->max_products;
    }

    public function canAddMarketplace()
    {
        if ($this->plan->hasUnlimitedMarketplaces()) {
            return true;
        }
        return $this->current_marketplaces_count < $this->plan->max_marketplaces;
    }

    public function canProcessOrder()
    {
        if ($this->plan->hasUnlimitedOrders()) {
            return true;
        }
        return $this->current_month_orders_count < $this->plan->max_orders_per_month;
    }

    // Kullanım sayaçlarını artır
    public function incrementProducts()
    {
        $this->increment('current_products_count');
    }

    public function decrementProducts()
    {
        $this->decrement('current_products_count');
    }

    public function incrementMarketplaces()
    {
        $this->increment('current_marketplaces_count');
    }

    public function decrementMarketplaces()
    {
        $this->decrement('current_marketplaces_count');
    }

    public function incrementOrders()
    {
        $this->increment('current_month_orders_count');
    }

    // Aylık kullanım resetleme
    public function resetMonthlyUsage()
    {
        $this->update([
            'current_month_orders_count' => 0,
            'usage_reset_at' => now()->addMonth(),
        ]);
    }
}
