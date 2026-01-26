<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Order;
use App\Models\Product;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_plans' => Plan::count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'total_orders' => Order::count(),
            'total_products' => Product::count(),
            'total_revenue' => Subscription::sum('amount'),
        ];

        $latest_users = User::latest()->take(5)->get();
        $latest_subscriptions = Subscription::with(['user', 'plan'])->latest()->take(5)->get();

        return view('super-admin.dashboard', compact('stats', 'latest_users', 'latest_subscriptions'));
    }
}
