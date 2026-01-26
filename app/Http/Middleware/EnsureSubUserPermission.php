<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubUserPermission
{
    private const ROUTE_PERMISSION_MAP = [
        'admin.dashboard' => 'dashboard',
        'admin.products.' => 'products',
        'admin.marketplace-products.' => 'products',
        'admin.categories.' => 'products',
        'admin.brands.' => 'products',
        'admin.orders.' => 'orders',
        'admin.customers.' => 'customers',
        'admin.reports.index' => 'reports.orders',
        'admin.reports.top-products' => 'reports.top_products',
        'admin.reports.sold-products' => 'reports.sold_products',
        'admin.reports.category-sales' => 'reports.category_sales',
        'admin.reports.brand-sales' => 'reports.brand_sales',
        'admin.reports.vat' => 'reports.vat',
        'admin.reports.commission' => 'reports.commission',
        'admin.reports.stock-value' => 'reports.stock_value',
        'admin.integrations.' => 'integrations',
        'admin.addons.' => 'addons',
        'admin.subscription' => 'subscription',
        'admin.subscription.' => 'subscription',
        'admin.settings.' => 'settings',
        'admin.help.' => 'help',
        'admin.tickets.' => 'tickets',
        'admin.invoices.' => 'invoices',
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
