<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Product;
use App\Models\Order;
use App\Models\MarketplaceCredential;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Carbon\Carbon;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest()->paginate(20);

        return view('super-admin.users.index', compact('users'));
    }

    public function edit(User $user)
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return view('super-admin.users.edit', compact('user', 'plans'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:super_admin,client',
            'is_active' => 'boolean',
            'plan_id' => 'nullable|exists:plans,id',
            'billing_period' => 'nullable|in:monthly,yearly',
            'assign_plan' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $user->update($validated);

        if ($request->boolean('assign_plan') && $request->filled('plan_id')) {
            $plan = Plan::findOrFail($request->plan_id);
            $billingPeriod = $request->input('billing_period', $plan->billing_period);
            $amount = $billingPeriod === 'yearly' && $plan->yearly_price
                ? $plan->yearly_price
                : $plan->price;

            $startsAt = Carbon::now();
            $endsAt = $billingPeriod === 'yearly'
                ? $startsAt->copy()->addYear()
                : $startsAt->copy()->addMonth();

            $currentProducts = Product::where('user_id', $user->id)->count();
            $currentMarketplaces = MarketplaceCredential::where('user_id', $user->id)
                ->where('is_active', true)
                ->count();
            $currentMonthOrders = Order::where('user_id', $user->id)
                ->whereMonth('order_date', now()->month)
                ->whereYear('order_date', now()->year)
                ->count();

            $active = $user->subscription;
            if ($active && $active->isActive()) {
                $active->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'ends_at' => now(),
                    'auto_renew' => false,
                ]);
            }

            $newSubscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'amount' => $amount,
                'billing_period' => $billingPeriod,
                'auto_renew' => true,
                'current_products_count' => $currentProducts,
                'current_marketplaces_count' => $currentMarketplaces,
                'current_month_orders_count' => $currentMonthOrders,
                'usage_reset_at' => now()->addMonth(),
            ]);

            $invoiceNumber = 'INV-' . now()->format('Ymd') . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            Invoice::create([
                'user_id' => $user->id,
                'subscription_id' => $newSubscription->id,
                'invoice_number' => $invoiceNumber,
                'amount' => $amount,
                'currency' => 'TRY',
                'status' => 'paid',
                'issued_at' => now(),
                'paid_at' => now(),
                'billing_name' => $user->billing_name ?: $user->name,
                'billing_email' => $user->billing_email ?: $user->email,
                'billing_address' => $user->billing_address,
            ]);
        }

        return redirect()->route('super-admin.users.index')
            ->with('success', 'Kullanıcı başarıyla güncellendi.');
    }
}
