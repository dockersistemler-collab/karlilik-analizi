<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    public const MODULES = [
        'category_mapping' => 'Kategori Eşitleme',
        'sub_users' => 'Alt Kullanıcılar',
        'tickets' => 'Destek (Ticket)',
        'quick_actions' => 'Hızlı Menü',
    ];

    public const REPORT_MODULES = [
        'reports.top_products' => 'Çok Satan Ürünler',
        'reports.sold_products' => 'Satılan Ürünler',
        'reports.orders' => 'Sipariş ve Ciro',
        'reports.category_sales' => 'Kategori Bazlı Satış',
        'reports.brand_sales' => 'Marka Bazlı Satış',
        'reports.vat' => 'KDV Raporu',
        'reports.commission' => 'Komisyon Raporu',
        'reports.stock_value' => 'Stok Değeri',
    ];

    public const EXPORT_MODULES = [
        'exports.products' => 'Ürünler Export',
        'exports.orders' => 'Siparişler Export',
        'exports.invoices' => 'Faturalar Export',
        'exports.reports.orders' => 'Raporlar: Sipariş ve Ciro Export',
        'exports.reports.top_products' => 'Raporlar: Çok Satan Ürünler Export',
    ];

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

        if (!array_key_exists('modules', $features) || !is_array($features['modules'])) {
            return [];
        }

        $modules = array_values(array_filter($features['modules'], fn ($m) => is_string($m) && trim($m) !== ''));
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

        foreach ($modules as $module) {
            if (!is_string($module) || $module === '') {
                continue;
            }
            if ($module === $moduleKey) {
                return true;
            }
            if (str_starts_with($module, $moduleKey . '.')) {
                return true;
            }
            if (str_starts_with($moduleKey, $module . '.')) {
                return true;
            }
        }

        return false;
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
