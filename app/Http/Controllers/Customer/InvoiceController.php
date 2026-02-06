<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $invoices = Invoice::query()
            ->where('user_id', $user->id)
            ->latest('issued_at')
            ->paginate(20)
            ->withQueryString();

        return view('customer.invoices.index', compact('invoices'));
    }

    public function show(Request $request, Invoice $invoice)
    {
        $invoice = $this->scopedInvoice($request, $invoice);
        Gate::authorize('view', $invoice);

        $downloadUrl = URL::signedRoute('portal.invoices.download', $invoice);

        return view('customer.invoices.show', compact('invoice', 'downloadUrl'));
    }

    public function download(Request $request, Invoice $invoice): Response
    {
        $invoice = $this->scopedInvoice($request, $invoice);
        Gate::authorize('download', $invoice);

        $path = (string) ($invoice->pdf_path ?? '');
        if ($path !== '' && Storage::disk('local')->exists($path)) {
            $filename = ($invoice->invoice_number ?: 'invoice').'.pdf';
            return Storage::disk('local')->download($path, $filename);
        }
$pdf = Pdf::loadView('customer.invoices.pdf', ['invoice' => $invoice]);
        $filename = ($invoice->invoice_number ?: 'invoice').'.pdf';

        return $pdf->download($filename);
    }

    private function scopedInvoice(Request $request, Invoice $invoice): Invoice
    {
        $user = $request->user();

        return Invoice::query()
            ->where('user_id', $user->id)
            ->whereKey($invoice->id)
            ->firstOrFail();
    }
}
