<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Product;
use App\Models\Order;
use App\Models\MarketplaceCredential;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Referral;
use App\Models\ReferralProgram;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $subscription = $user?->subscription;

        return view('admin.subscription', compact('subscription'));
    }

    public function history(Request $request): View
    {
        $user = $request->user();
        $subscriptions = $user?->subscriptions()
            ->with('plan')
            ->latest()
            ->paginate(20);

        return view('admin.subscription-history', compact('subscriptions'));
    }

    public function invoices(Request $request): View
    {
        $user = $request->user();
        $query = $user?->invoices()->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('issued_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('issued_at', '<=', $request->date_to);
        }

        $invoices = $query->paginate(20)->withQueryString();

        return view('admin.invoices', compact('invoices'));
    }

    public function showInvoice(Request $request, Invoice $invoice): View
    {
        $user = $request->user();
        if (!$user || $invoice->user_id !== $user->id) {
            abort(403);
        }

        $invoice->load(['subscription.plan']);

        return view('admin.invoice-show', compact('invoice'));
    }

    public function exportInvoices(Request $request)
    {
        $user = $request->user();
        $query = $user?->invoices()->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('issued_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('issued_at', '<=', $request->date_to);
        }

        $filename = 'invoices-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Invoice No', 'Issued At', 'Amount', 'Currency', 'Status']);
            $query->chunk(200, function ($invoices) use ($handle) {
                foreach ($invoices as $invoice) {
                    fputcsv($handle, [
                        $invoice->invoice_number,
                        optional($invoice->issued_at)->format('Y-m-d'),
                        $invoice->amount,
                        $invoice->currency,
                        $invoice->status,
                    ]);
                }
            });
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function createInvoice(Request $request): View
    {
        return view('admin.invoice-create');
    }

    public function storeInvoice(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'billing_address' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|in:TRY,USD,EUR',
            'status' => 'required|in:paid,pending,failed,refunded',
            'issued_at' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $invoiceNumber = 'INV-' . now()->format('Ymd') . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);

        Invoice::create([
            'user_id' => $user->id,
            'subscription_id' => null,
            'invoice_number' => $invoiceNumber,
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'status' => $validated['status'],
            'issued_at' => $validated['issued_at'],
            'paid_at' => $validated['status'] === 'paid' ? now() : null,
            'billing_name' => $validated['customer_name'],
            'billing_email' => $validated['customer_email'],
            'billing_address' => $validated['billing_address'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('admin.invoices.index')
            ->with('success', 'Fatura oluşturuldu.');
    }

    public function searchInvoiceCustomers(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        $query = trim((string) $request->query('q', ''));
        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $manualCustomers = Customer::query()
            ->where('user_id', $user->id)
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'like', '%'.$query.'%')
                    ->orWhere('email', 'like', '%'.$query.'%');
            })
            ->limit(30)
            ->get(['name', 'email', 'billing_address', 'phone']);

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->where(function ($builder) use ($query) {
                $builder->where('customer_name', 'like', '%'.$query.'%')
                    ->orWhere('customer_email', 'like', '%'.$query.'%');
            })
            ->latest('order_date')
            ->limit(30)
            ->get(['customer_name', 'customer_email', 'billing_address']);

        $fromOrders = $orders->map(function ($order) {
            return [
                'name' => $order->customer_name,
                'email' => $order->customer_email,
                'billing_address' => $order->billing_address,
            ];
        });

        $fromCustomers = $manualCustomers->map(function ($customer) {
            return [
                'name' => $customer->name,
                'email' => $customer->email,
                'billing_address' => $customer->billing_address,
                'phone' => $customer->phone,
            ];
        });

        $customers = $fromCustomers
            ->concat($fromOrders)
            ->filter(fn ($row) => !empty($row['email']))
            ->unique('email')
            ->values();

        return response()->json($customers);
    }

    public function store(Request $request, Plan $plan): RedirectResponse
    {
        $user = $request->user();

        if (!$user || !$user->isClient()) {
            abort(403);
        }

        if (!$plan->is_active) {
            return redirect()->route('pricing')
                ->with('info', 'Seçtiğiniz paket aktif değil.');
        }

        $currentSubscription = $user->subscription;
        if ($currentSubscription && $currentSubscription->isActive()) {
            if ($currentSubscription->plan_id === $plan->id) {
                return redirect()->route('admin.subscription')
                    ->with('info', 'Zaten bu paketi kullanıyorsunuz.');
            }
        }

        $billingPeriod = $plan->billing_period;
        $amount = $billingPeriod === 'yearly' && $plan->yearly_price
            ? $plan->yearly_price
            : $plan->price;

        $startsAt = Carbon::now();
        $endsAt = $billingPeriod === 'yearly'
            ? $startsAt->copy()->addYear()
            : $startsAt->copy()->addMonth();

        $amount = $this->applyReferrerCredit($user, $amount);
        [$amount, $endsAt] = $this->applyReferralRewards($user, $amount, $endsAt);

        $currentProducts = Product::where('user_id', $user->id)->count();
        $currentMarketplaces = MarketplaceCredential::where('user_id', $user->id)
            ->where('is_active', true)
            ->count();
        $currentMonthOrders = Order::where('user_id', $user->id)
            ->whereMonth('order_date', now()->month)
            ->whereYear('order_date', now()->year)
            ->count();

        if ($plan->max_products > 0 && $currentProducts > $plan->max_products) {
            return redirect()->route('pricing')
                ->with('info', 'Mevcut ürün sayınız bu paketin limitini aşıyor.');
        }

        if ($plan->max_marketplaces > 0 && $currentMarketplaces > $plan->max_marketplaces) {
            return redirect()->route('pricing')
                ->with('info', 'Mevcut mağaza sayınız bu paketin limitini aşıyor.');
        }

        if ($plan->max_orders_per_month > 0 && $currentMonthOrders > $plan->max_orders_per_month) {
            return redirect()->route('pricing')
                ->with('info', 'Bu ayki sipariş sayınız bu paketin limitini aşıyor.');
        }

        if ($currentSubscription && $currentSubscription->isActive()) {
            $currentSubscription->update([
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

        return redirect()->route('admin.dashboard')
            ->with('success', 'Aboneliğiniz başarıyla başlatıldı.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user || !$user->isClient()) {
            abort(403);
        }

        $subscription = $user->subscription;
        if (!$subscription || !$subscription->isActive()) {
            return redirect()->route('admin.subscription')
                ->with('info', 'Aktif abonelik bulunamadı.');
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'ends_at' => now(),
            'auto_renew' => false,
        ]);

        return redirect()->route('admin.subscription')
            ->with('success', 'Aboneliğiniz iptal edildi.');
    }

    public function renew(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user || !$user->isClient()) {
            abort(403);
        }

        $lastSubscription = $user->subscription;
        if (!$lastSubscription) {
            return redirect()->route('pricing')
                ->with('info', 'Yenilenecek bir abonelik bulunamadı.');
        }

        if ($lastSubscription->isActive()) {
            return redirect()->route('admin.subscription')
                ->with('info', 'Zaten aktif bir aboneliğiniz var.');
        }

        $plan = $lastSubscription->plan;
        if (!$plan || !$plan->is_active) {
            return redirect()->route('pricing')
                ->with('info', 'Abonelik paketi artık aktif değil. Lütfen yeni bir paket seçin.');
        }

        $billingPeriod = $lastSubscription->billing_period;
        $amount = $billingPeriod === 'yearly' && $plan->yearly_price
            ? $plan->yearly_price
            : $plan->price;

        $startsAt = Carbon::now();
        $endsAt = $billingPeriod === 'yearly'
            ? $startsAt->copy()->addYear()
            : $startsAt->copy()->addMonth();

        $amount = $this->applyReferrerCredit($user, $amount);

        $currentProducts = Product::where('user_id', $user->id)->count();
        $currentMarketplaces = MarketplaceCredential::where('user_id', $user->id)
            ->where('is_active', true)
            ->count();
        $currentMonthOrders = Order::where('user_id', $user->id)
            ->whereMonth('order_date', now()->month)
            ->whereYear('order_date', now()->year)
            ->count();

        if ($plan->max_products > 0 && $currentProducts > $plan->max_products) {
            return redirect()->route('pricing')
                ->with('info', 'Mevcut ürün sayınız bu paketin limitini aşıyor.');
        }

        if ($plan->max_marketplaces > 0 && $currentMarketplaces > $plan->max_marketplaces) {
            return redirect()->route('pricing')
                ->with('info', 'Mevcut mağaza sayınız bu paketin limitini aşıyor.');
        }

        if ($plan->max_orders_per_month > 0 && $currentMonthOrders > $plan->max_orders_per_month) {
            return redirect()->route('pricing')
                ->with('info', 'Bu ayki sipariş sayınız bu paketin limitini aşıyor.');
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

        return redirect()->route('admin.subscription')
            ->with('success', 'Aboneliğiniz yenilendi.');
    }

    private function applyReferralRewards($user, float $amount, Carbon $endsAt): array
    {
        $referral = Referral::query()
            ->where('status', 'pending')
            ->where(function ($query) use ($user) {
                $query->where('referred_user_id', $user->id)
                    ->orWhere('referred_email', $user->email);
            })
            ->latest()
            ->first();

        if (!$referral) {
            return [$amount, $endsAt];
        }

        $program = ReferralProgram::active()->latest()->first();
        if (!$program) {
            return [$amount, $endsAt];
        }

        $referral->program_id = $program->id;

        if ($program->max_uses_per_referrer_per_year > 0) {
            $count = Referral::query()
                ->where('referrer_id', $referral->referrer_id)
                ->where('status', 'rewarded')
                ->where('rewarded_at', '>=', now()->subYear())
                ->count();

            if ($count >= $program->max_uses_per_referrer_per_year) {
                $referral->status = 'limit_reached';
                $referral->save();

                return [$amount, $endsAt];
            }
        }

        $referral->referrer_reward_type = $program->referrer_reward_type;
        $referral->referrer_reward_value = $program->referrer_reward_value;
        $referral->referred_reward_type = $program->referred_reward_type;
        $referral->referred_reward_value = $program->referred_reward_value;

        if ($program->referred_reward_type === 'percent' && $program->referred_reward_value) {
            $discount = ($amount * (float) $program->referred_reward_value) / 100;
            $amount = max($amount - $discount, 0);
            $referral->applied_discount_amount = $discount;
        }

        if ($program->referred_reward_type === 'duration' && $program->referred_reward_value) {
            $months = (int) $program->referred_reward_value;
            if ($months > 0) {
                $endsAt = $endsAt->copy()->addMonths($months);
            }
        }

        $referrer = $referral->referrer;
        if ($referrer) {
            if ($program->referrer_reward_type === 'duration' && $program->referrer_reward_value) {
                $months = (int) $program->referrer_reward_value;
                if ($months > 0 && $referrer->subscription && $referrer->subscription->isActive()) {
                    $referrer->subscription->update([
                        'ends_at' => $referrer->subscription->ends_at->copy()->addMonths($months),
                    ]);
                }
            }

            if ($program->referrer_reward_type === 'percent' && $program->referrer_reward_value) {
                $referral->referrer_discount_amount = (float) $program->referrer_reward_value;
            }
        }

        $referral->status = 'rewarded';
        $referral->rewarded_at = now();
        $referral->save();

        return [$amount, $endsAt];
    }

    private function applyReferrerCredit($user, float $amount): float
    {
        $credit = Referral::query()
            ->where('referrer_id', $user->id)
            ->where('status', 'rewarded')
            ->where('referrer_reward_type', 'percent')
            ->whereNotNull('referrer_discount_amount')
            ->whereNull('referrer_discount_consumed_at')
            ->orderBy('rewarded_at')
            ->first();

        if (!$credit) {
            return $amount;
        }

        $discount = ($amount * (float) $credit->referrer_discount_amount) / 100;
        $amount = max($amount - $discount, 0);

        $credit->referrer_discount_consumed_at = now();
        $credit->save();

        return $amount;
    }
}
