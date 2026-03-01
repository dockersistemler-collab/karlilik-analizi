<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubUserPermission
{
    private const ROUTE_PERMISSION_MAP = [
        'portal.dashboard' => 'dashboard',
        'portal.products.' => 'products',
        'portal.marketplace-products.' => 'products',
        'portal.categories.' => 'products',
        'portal.brands.' => 'products',
        'portal.orders.labels.' => 'orders.bulk_cargo_label_print',
        'portal.orders.' => 'orders',
        'portal.shipments.' => 'orders',
        'portal.customers.' => 'customers',
        'portal.reports.index' => 'reports.orders',
        'portal.reports.top-products' => 'reports.top_products',
        'portal.reports.sold-products' => 'reports.sold_products',
        'portal.reports.category-sales' => 'reports.category_sales',
        'portal.reports.brand-sales' => 'reports.brand_sales',
        'portal.reports.vat' => 'reports.vat',
        'portal.reports.commission' => 'reports.commission',
        'portal.commission-tariffs.' => 'reports.commission_tariffs',
        'portal.campaigns.' => 'reports',
        'portal.reports.stock-value' => 'reports.stock_value',
        'portal.reports.order-profitability' => 'reports.profitability',
        'portal.profitability.' => 'reports.profitability',
        'portal.profit-engine.' => 'reports.profit_engine',
        'portal.decision-center.' => 'reports',
        'portal.marketplace-risk.' => 'reports.marketplace_risk',
        'portal.action-engine.' => 'reports.action_engine',
        'portal.buybox.' => 'reports.buybox_engine',
        'portal.control-tower.' => 'control_tower',
        'portal.communication-center.' => 'communication_center',
        'portal.settlements.reconcile' => 'settlements.manage',
        'portal.settlements.disputes.from-findings' => 'settlements.manage',
        'portal.settlements.disputes.update' => 'settlements.manage',
        'portal.settlements.disputes.bulk-status' => 'settlements.manage',
        'portal.settlements.' => 'settlements.view',
        'ne-kazanirim.' => 'reports.profitability',
        'portal.integrations.' => 'integrations',
        'portal.addons.' => 'addons',
        'portal.subscription' => 'subscription',
        'portal.subscription.' => 'subscription',
        'portal.settings.' => 'settings',
        'portal.help.' => 'help',
        'portal.tickets.' => 'tickets',
        'portal.invoices.' => 'invoices',
        'portal.notification-hub.' => 'settings',
        'portal.inventory.admin.' => 'products',
        'portal.inventory.user.' => 'products',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('subuser')->check()) {
            return $next($request);
        }
$routeName = $request->route()?->getName();
        if (!$routeName) {
            abort(403);
        }
$permissionKey = $this->resolvePermissionKey($routeName);
        if (!$permissionKey) {
            abort(403);
        }
$subUser = Auth::guard('subuser')->user();
        if (!$subUser || !$subUser->hasPermission($permissionKey)) {
            abort(403);
        }

        return $next($request);
    }

    private function resolvePermissionKey(string $routeName): ?string
    {
        foreach (self::ROUTE_PERMISSION_MAP as $prefix => $permission) {
            if ($routeName === $prefix || str_starts_with($routeName, $prefix)) {
                return $permission;
            }
        }

        return null;
    }
}
