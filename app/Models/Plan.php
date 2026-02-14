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

    public function enabledModules(): array
    {
        $features = $this->features;
        if (!is_array($features)) {
            return [];
        }
$modules = [];
        if (array_key_exists('modules', $features) && is_array($features['modules'])) {
            $modules = array_values(array_filter($features['modules'], fn ($m) => is_string($m) && trim($m) !== ''));
        }

        // TODO: Remove legacy plan_modules merge after migration window closes.
        if (config('app.read_legacy_plan_modules', true)) {
            if (array_key_exists('plan_modules', $features) && is_array($features['plan_modules'])) {
                $legacy = array_values(array_filter($features['plan_modules'], fn ($m) => is_string($m) && trim($m) !== ''));
                $modules = array_merge($modules, $legacy);
            }
        }
$modules = array_values(array_unique(array_map('trim', $modules)));
        $modules = array_values(array_filter($modules, fn ($m) => $m !== ''));

        if (empty($modules)) {
            return [];
        }

        if (in_array('*', $modules, true)) {
            return ['*'];
        }

        return $modules;
    }

    public function hasModule(string $moduleKey): bool
    {
        $modules = $this->enabledModules();
        if ($modules === ['*']) {
            return true;
        }

        if (empty($modules)) {
            return false;
        }

        return in_array($moduleKey, $modules, true);
    }


    public function withModules(array $modules): array
    {
        $normalized = array_values(array_unique(array_values(array_filter($modules, fn ($m) => is_string($m) && $m !== ''))));

        $current = $this->features;
        if (is_array($current) && function_exists('array_is_list') && array_is_list($current)) {
            return [
                'marketing' => $current,
                'modules' => $normalized,
            ];
        }

        if (is_array($current)) {
            $current['modules'] = $normalized;
            return $current;
        }

        return [
            'modules' => $normalized,
        ];
    }

}



