<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'yearly_price',
        'billing_period',
        'max_products',
        'max_marketplaces',
        'max_orders_per_month',
        'max_tickets_per_month',
        'api_access',
        'advanced_reports',
        'priority_support',
        'custom_integrations',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'api_access' => 'boolean',
        'advanced_reports' => 'boolean',
        'priority_support' => 'boolean',
        'custom_integrations' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
    ];

    // İlişkiler
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    // Helper metodlar
    public function hasUnlimitedProducts()
    {
        return $this->max_products === 0;
    }

    public function hasUnlimitedMarketplaces()
    {
        return $this->max_marketplaces === 0;
    }

    public function hasUnlimitedOrders()
    {
        return $this->max_orders_per_month === 0;
    }

    public function hasUnlimitedTickets()
    {
        return $this->max_tickets_per_month === 0;
    }
}
