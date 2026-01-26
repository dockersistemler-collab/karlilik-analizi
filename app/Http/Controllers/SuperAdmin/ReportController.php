<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Order;
use App\Models\User;

class ReportController extends Controller
{
    public function index()
    {
        $totalRevenue = Subscription::sum('amount');
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $totalOrders = Order::count();
        $totalClients = User::where('role', 'client')->count();

        return view('super-admin.reports.index', compact(
            'totalRevenue',
            'activeSubscriptions',
            'totalOrders',
            'totalClients'
        ));
    }
}
