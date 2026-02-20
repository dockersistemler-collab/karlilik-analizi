<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\MarketplaceCredential;
use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserModule;
use App\Services\Entitlements\EntitlementService;
use App\Services\Purchases\ModulePurchaseService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->latest();

        $search = trim((string) $request->query('q', ''));
        $role = trim((string) $request->query('role', ''));
        $status = trim((string) $request->query('status', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        if ($role !== '') {
            $query->where('role', $role);
        }

        if ($status !== '') {
            $query->where('is_active', $status === 'active');
        }

        if ($dateFrom !== '') {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $users = $query->paginate(20)->withQueryString();
        $subUsersModule = Module::query()->where('code', 'feature.sub_users')->first();
        $subUsersModuleStates = [];
        $subUsersModulePending = [];

        if ($subUsersModule && $users->count() > 0) {
            $userIds = $users->pluck('id')->all();

            $subUsersModuleStates = UserModule::query()
                ->where('module_id', $subUsersModule->id)
                ->whereIn('user_id', $userIds)
                ->pluck('status', 'user_id')
                ->toArray();

            $pendingUserIds = ModulePurchase::query()
                ->where('module_id', $subUsersModule->id)
                ->where('provider', 'manual')
                ->where('status', 'pending')
                ->whereIn('user_id', $userIds)
                ->pluck('user_id')
                ->all();

            $subUsersModulePending = array_fill_keys($pendingUserIds, true);
        }

        $roles = [
            'super_admin' => 'Super Admin',
            'support_agent' => 'Destek',
            'client' => 'Musteri',
        ];

        return view('super-admin.users.index', compact(
            'users',
            'roles',
            'search',
            'role',
            'status',
            'dateFrom',
            'dateTo',
            'subUsersModule',
            'subUsersModuleStates',
            'subUsersModulePending'
        ));
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
            'role' => 'required|in:super_admin,client,support_agent',
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
            ->with('success', 'Kullanici basariyla guncellendi.');
    }

    public function toggleSubUsersModule(
        Request $request,
        User $user,
        EntitlementService $entitlements,
        ModulePurchaseService $purchaseService
    ): RedirectResponse {
        if ($user->role !== 'client') {
            return back()->with('info', 'Bu islem yalnizca client hesaplar icin kullanilabilir.');
        }

        $module = Module::query()->where('code', 'feature.sub_users')->first();
        if (!$module) {
            return back()->with('error', 'feature.sub_users modulu bulunamadi.');
        }

        $enable = $request->boolean('enable');

        if ($enable) {
            $pendingPurchase = ModulePurchase::query()
                ->where('user_id', $user->id)
                ->where('module_id', $module->id)
                ->where('provider', 'manual')
                ->where('status', 'pending')
                ->latest('id')
                ->first();

            if ($pendingPurchase) {
                $purchaseService->markPaid($pendingPurchase, [
                    'approved_by' => auth()->id(),
                    'approval_source' => 'super_admin_user_list',
                ], 'manual:sub-users:' . $pendingPurchase->id);
            } else {
                $entitlements->setModuleStatus(
                    $user,
                    'feature.sub_users',
                    'active',
                    null,
                    Carbon::now(),
                    [
                        'granted_by' => auth()->id(),
                        'grant_source' => 'super_admin_user_list',
                    ]
                );
            }

            return back()->with('success', 'Alt kullanici modulu aktif edildi.');
        }

        $entitlements->setModuleStatus(
            $user,
            'feature.sub_users',
            'inactive',
            Carbon::now(),
            null,
            [
                'updated_by' => auth()->id(),
                'update_source' => 'super_admin_user_list',
            ]
        );

        return back()->with('success', 'Alt kullanici modulu pasif edildi.');
    }
}
