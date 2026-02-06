<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Services\Webhooks\WebhookService;
use Symfony\Component\HttpFoundation\Response;

class EInvoiceApiController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        $query = EInvoice::query()
            ->where('user_id', $user->id)
            ->withCount(['items', 'events'])
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }

        if ($request->filled('marketplace')) {
            $query->where('marketplace', (string) $request->query('marketplace'));
        }

        if ($request->filled('type')) {
            $query->where('type', (string) $request->query('type'));
        }

        if ($request->filled('updated_since')) {
            $since = Carbon::parse((string) $request->query('updated_since'));
            $query->where('updated_at', '>=', $since);
        }
$perPage = (int) $request->query('per_page', 20);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 20;

        $paginator = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (EInvoice $inv) => $this->serializeInvoice($inv))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, EInvoice $einvoice): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($einvoice->user_id !== $user->id) {
            abort(404);
        }
$includeItems = $request->boolean('include_items', true);
        $includeEvents = $request->boolean('include_events', false);

        if ($includeItems) {
            $einvoice->loadMissing('items');
        }
        if ($includeEvents) {
            $einvoice->loadMissing('events');
        }
$payload = $this->serializeInvoice($einvoice);
        if ($includeItems) {
            $payload['items'] = $einvoice->items->map(fn ($i) => [
                'id' => $i->id,
                'sku' => $i->sku,
                'name' => $i->name,
                'quantity' => (float) $i->quantity,
                'unit_price' => (float) $i->unit_price,
                'vat_rate' => (float) $i->vat_rate,
                'vat_amount' => (float) $i->vat_amount,
                'discount_amount' => (float) $i->discount_amount,
                'total' => (float) $i->total,
            ])->values();
        }
        if ($includeEvents) {
            $payload['events'] = $einvoice->events->map(fn ($e) => [
                'id' => $e->id,
                'type' => $e->type,
                'payload' => $e->payload,
                'created_at' => $e->created_at?->toISOString(),
            ])->values();
        }

        return response()->json($payload);
    }

    public function pdf(Request $request, EInvoice $einvoice): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($einvoice->user_id !== $user->id) {
            abort(404);
        }

        if (!$einvoice->pdf_path || !Storage::disk('local')->exists($einvoice->pdf_path)) {
            abort(404);
        }
$filename = ($einvoice->invoice_no ?: 'einvoice').'.pdf';
        return Storage::disk('local')->download($einvoice->pdf_path, $filename);
    }

    public function providerStatus(Request $request, EInvoice $einvoice, WebhookService $webhooks): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($einvoice->user_id !== $user->id) {
            abort(404);
        }

        if (!$user->tokenCan('einvoices:status')) {
            abort(403);
        }
$validated = $request->validate(['provider_status' => 'required|string|max:100',
            'provider_invoice_id' => 'nullable|string|max:255',
            'raw' => 'nullable|array',
        ]);

        $einvoice->provider_status = (string) $validated['provider_status'];
        if (!empty($validated['provider_invoice_id'])) {
            $einvoice->provider_invoice_id = (string) $validated['provider_invoice_id'];
        }
$raw = $validated['raw'] ?? null;
        if ($raw !== null) {
            $existing = is_array($einvoice->provider_payload_json) ? $einvoice->provider_payload_json : [];
            $einvoice->provider_payload_json = array_merge($existing, $raw);
        }
$einvoice->save();

        $einvoice->events()->create(['type' => 'provider_status_updated',
            'payload' => [
                'provider_status' => $einvoice->provider_status,
                'provider_invoice_id' => $einvoice->provider_invoice_id,
                'raw' => $raw,
            ],
        ]);

        $webhooks->dispatchEvent($user, 'einvoice.status_changed', [
            'einvoice' => [
                'id' => $einvoice->id,
                'status' => $einvoice->status,
                'type' => $einvoice->type,
                'invoice_no' => $einvoice->invoice_no,
                'issued_at' => $einvoice->issued_at?->toISOString(), 'totals' => [
                    'subtotal' => (float) $einvoice->subtotal,
                    'tax_total' => (float) $einvoice->tax_total,
                    'discount_total' => (float) $einvoice->discount_total,
                    'grand_total' => (float) $einvoice->grand_total,
                    'currency' => $einvoice->currency,
                ],
                'marketplace' => $einvoice->marketplace,
                'order_no' => $einvoice->marketplace_order_no,
                'provider' => $einvoice->provider,
                'provider_status' => $einvoice->provider_status,
            ],
            'user' => [
                'id' => $user->id,
            ],
        ]);

        return response()->json($this->serializeInvoice($einvoice));
    }

    /**
     * @return array<string,mixed>
     */
    private function serializeInvoice(EInvoice $inv): array
    {
        return [
            'id' => $inv->id,
            'invoice_no' => $inv->invoice_no,
            'status' => $inv->status,
            'type' => $inv->type,
            'issued_at' => $inv->issued_at?->toISOString(), 'marketplace' => $inv->marketplace,
            'marketplace_order_no' => $inv->marketplace_order_no,
            'buyer' => [
                'name' => $inv->buyer_name,
                'email' => $inv->buyer_email,
                'phone' => $inv->buyer_phone,
            ],
            'totals' => [
                'subtotal' => (float) $inv->subtotal,
                'tax_total' => (float) $inv->tax_total,
                'discount_total' => (float) $inv->discount_total,
                'grand_total' => (float) $inv->grand_total,
                'currency' => $inv->currency,
            ],
            'pdf_url' => route('api.v1.einvoices.pdf', $inv),
            'updated_at' => $inv->updated_at?->toISOString(),
        ];
    }
}
