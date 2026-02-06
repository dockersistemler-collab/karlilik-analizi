<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $last7 = $now->copy()->subDays(7);
        $last30 = $now->copy()->subDays(30);

        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'new_users_7d' => User::where('created_at', '>=', $last7)->count(),
            'total_plans' => Plan::count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'new_subscriptions_7d' => Subscription::where('created_at', '>=', $last7)->count(),
            'total_orders' => Order::count(),
            'orders_30d' => Order::where('created_at', '>=', $last30)->count(),
            'total_products' => Product::count(),
            'total_revenue' => Subscription::sum('amount'),
            'revenue_30d' => Subscription::where('created_at', '>=', $last30)->sum('amount'),
        ];

        $latest_users = User::latest()->take(5)->get();
        $latest_subscriptions = Subscription::with(['user', 'plan'])->latest()->take(5)->get();

        return view('super-admin.dashboard', compact('stats', 'latest_users', 'latest_subscriptions'));
    }
}
