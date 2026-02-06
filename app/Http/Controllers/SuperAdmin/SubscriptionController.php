<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\User;
use App\Services\BillingEventLogger;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Subscription::with(['user', 'plan'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        if ($request->filled('email')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('email', 'like', '%' . $request->email . '%');
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('starts_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('starts_at', '<=', $request->date_to);
        }
$subscriptions = $query->paginate(30)->withQueryString();
        $plans = Plan::orderBy('sort_order')->orderBy('price')->get();

        return view('super-admin.subscriptions.index', compact('subscriptions', 'plans'));
    }

    public function invoices(Request $request)
    {
        $query = Invoice::with('user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('email')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('email', 'like', '%' . $request->email . '%');
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('issued_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('issued_at', '<=', $request->date_to);
        }
$invoices = $query->paginate(30)->withQueryString();

        return view('super-admin.invoices.index', compact('invoices'));
    }

    public function createInvoice(Request $request)
    {
        $clients = User::query()->where('role', 'client')->orderBy('name')->get();

        return view('super-admin.invoices.create', compact('clients'));
    }

    public function storeInvoice(Request $request, BillingEventLogger $events)
    {
        $validated = $request->validate(['user_id' => 'required|integer|exists:users,id',
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

        $invoice = Invoice::create([
            'user_id' => $validated['user_id'],
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
        $events->record(['tenant_id' => (int) $validated['user_id'],
            'user_id' => (int) $validated['user_id'],
            'invoice_id' => $invoice->id,
            'type' => 'invoice.created',
            'status' => $invoice->status,
            'amount' => $invoice->amount,
            'currency' => $invoice->currency,
            'provider' => 'manual',
            'payload' => [
                'invoice_number' => $invoice->invoice_number,
            ],
        ]);
        if ($invoice->status === 'paid') {
            $events->record(['tenant_id' => (int) $validated['user_id'],
                'user_id' => (int) $validated['user_id'],
                'invoice_id' => $invoice->id,
                'type' => 'invoice.paid',
                'status' => $invoice->status,
                'amount' => $invoice->amount,
                'currency' => $invoice->currency,
                'provider' => 'manual',
            ]);
        }

        return redirect()->route('super-admin.invoices.index')
            ->with('success', 'Fatura oluşturuldu.');
    }

    public function searchInvoiceSubscribers(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }
$users = User::query()
            ->where('role', 'client')
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'like', '%'.$query.'%')
                    ->orWhere('email', 'like', '%'.$query.'%');
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }

    public function exportInvoices(Request $request)
    {
        $query = Invoice::with('user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('email')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('email', 'like', '%' . $request->email . '%');
            });
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
            fputcsv($handle, ['Invoice No', 'User', 'Email', 'Issued At', 'Amount', 'Currency', 'Status']);
            $query->chunk(200, function ($invoices) use ($handle) {
                foreach ($invoices as $invoice) {
                    fputcsv($handle, [
                        $invoice->invoice_number,
                        $invoice->user?->name, $invoice->user?->email,
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

    public function exportSubscriptions(Request $request)
    {
        $query = Subscription::with(['user', 'plan'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        if ($request->filled('email')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('email', 'like', '%' . $request->email . '%');
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('starts_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('starts_at', '<=', $request->date_to);
        }
$filename = 'subscriptions-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['User', 'Email', 'Plan', 'Starts At', 'Ends At', 'Amount', 'Status', 'Billing']);
            $query->chunk(200, function ($subs) use ($handle) {
                foreach ($subs as $subscription) {
                    fputcsv($handle, [
                        $subscription->user?->name, $subscription->user?->email, $subscription->plan?->name,
                        optional($subscription->starts_at)->format('Y-m-d'),
                        optional($subscription->ends_at)->format('Y-m-d'),
                        $subscription->amount,
                        $subscription->status,
                        $subscription->billing_period,
                    ]);
                }
            });
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function showInvoice(Invoice $invoice)
    {
        $invoice->load(['user', 'subscription.plan']);

        return view('super-admin.invoices.show', compact('invoice'));
    }
}
