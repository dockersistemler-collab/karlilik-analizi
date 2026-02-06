<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EInvoice;
use App\Models\Order;
use App\Services\EInvoices\EInvoiceService;
use App\Support\SupportUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class EInvoiceController extends Controller
{
    public function __construct(private readonly EInvoiceService $service)
    {
    }

    public function index(Request $request): View
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 401);

        $query = EInvoice::query()
            ->where('user_id', $user->id)
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }

        if ($request->filled('marketplace')) {
            $query->where('marketplace', (string) $request->query('marketplace'));
        }

        if ($request->filled('order_no')) {
            $orderNo = (string) $request->query('order_no');
            $query->where(function ($q) use ($orderNo) {
                $q->where('marketplace_order_no', 'like', "%{$orderNo}%")
                    ->orWhere('source_id', $orderNo);
            });
        }

        if ($request->filled('invoice_no')) {
            $invoiceNo = (string) $request->query('invoice_no');
            $query->where('invoice_no', 'like', "%{$invoiceNo}%");
        }
$einvoices = $query->paginate(20)->withQueryString();

        return view('admin.einvoices.index', compact('einvoices'));
    }

    public function show(Request $request, EInvoice $einvoice): View
    {
        $this->authorize('view', $einvoice);
        $einvoice->loadMissing(['items', 'events']);

        return view('admin.einvoices.show', compact('einvoice'));
    }

    public function createFromOrder(Request $request, Order $order): RedirectResponse
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 401);
        if (!$user->isSuperAdmin() && $order->user_id !== $user->id) {
            abort(403);
        }
$einvoice = $this->service->createDraftFromOrder($order);

        return redirect()
            ->route('portal.einvoices.show', $einvoice)
            ->with('success', 'E-Fatura taslağı oluşturuldu.');
    }

    public function issue(Request $request, EInvoice $einvoice): RedirectResponse
    {
        $this->authorize('update', $einvoice);

        $this->service->issue($einvoice);

        return back()->with('success', 'E-Fatura düzenlendi.');
    }

    public function createReturn(Request $request, EInvoice $einvoice): RedirectResponse
    {
        $this->authorize('update', $einvoice);

        $reason = $request->input('reason');
        $return = $this->service->createReturnFromInvoice($einvoice, is_string($reason) ? $reason : null);

        return redirect()
            ->route('portal.einvoices.show', $return)
            ->with('success', 'İade faturası taslağı oluşturuldu.');
    }

    public function createCreditNote(Request $request, EInvoice $einvoice): RedirectResponse
    {
        $this->authorize('update', $einvoice);

        $validated = $request->validate(['items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer',
            'items.*.qty' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string|max:1000',
        ]);

        $credit = $this->service->createCreditNoteFromInvoice($einvoice,
            $validated['items'],
            $validated['reason'] ?? null
        );

        return redirect()
            ->route('portal.einvoices.show', $credit)
            ->with('success', 'Kısmi iade (credit note) taslağı oluşturuldu.');
    }

    public function cancel(Request $request, EInvoice $einvoice): RedirectResponse
    {
        $this->authorize('update', $einvoice);

        $validated = $request->validate(['reason' => 'required|string|max:1000',
        ]);

        $this->service->cancelInvoice($einvoice, (string) $validated['reason']);

        return back()->with('success', 'Fatura iptal edildi.');
    }

    public function pdf(Request $request, EInvoice $einvoice): Response
    {
        $this->authorize('view', $einvoice);
        $einvoice->loadMissing(['items', 'user']);

        if ($einvoice->pdf_path && Storage::disk('local')->exists($einvoice->pdf_path)) {
            $filename = ($einvoice->invoice_no ?: 'einvoice').'.pdf';
            return Storage::disk('local')->download($einvoice->pdf_path, $filename);
        }

        return response()->view('admin.einvoices.pdf', ['invoice' => $einvoice]);
    }
}


