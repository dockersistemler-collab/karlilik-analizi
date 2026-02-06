<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\WebhookEndpoint;
use App\Services\Entitlements\EntitlementService;
use App\Services\Webhooks\WebhookService;
use App\Services\Webhooks\WebhookUrlGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WebhookEndpointController extends Controller
{
    /**
     * @return array<string,string>
     */
    private function availableEvents(): array
    {
        return [
            'einvoice.*' => 'E-Fatura: Tüm Eventler (einvoice.*)',
            'einvoice.created' => 'E-Fatura Oluşturuldu (einvoice.created)',
            'einvoice.issued' => 'E-Fatura Düzenlendi (einvoice.issued)',
            'einvoice.sent' => 'Provider Gönderildi (einvoice.sent)',
            'einvoice.status_changed' => 'Provider Durumu Güncellendi (einvoice.status_changed)',
            'einvoice.cancelled' => 'İptal Edildi (einvoice.cancelled)',
            'einvoice.return_created' => 'İade Taslağı Oluşturuldu (einvoice.return_created)',
            'einvoice.credit_note_created' => 'Kısmi İade Taslağı Oluşturuldu (einvoice.credit_note_created)',
            'webhook.test' => 'Test Event (webhook.test)',
        ];
    }

    public function index(Request $request, EntitlementService $entitlements): View
    {
        $user = $request->user();
        abort_unless($user, 401);

        $module = Module::query()->where('code', WebhookService::MODULE_CODE)->first();
        $hasAccess = $entitlements->hasModule($user, WebhookService::MODULE_CODE);

        $endpoints = WebhookEndpoint::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        $since = now()->subHour();
        $endpointMetrics = \App\Models\WebhookDelivery::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $since)
            ->selectRaw('webhook_endpoint_id, COUNT(*) as attempts, SUM(CASE WHEN status = \"failed\" THEN 1 ELSE 0 END) as fails')
            ->groupBy('webhook_endpoint_id')
            ->get()
            ->keyBy('webhook_endpoint_id')
            ->map(fn ($row) => [
                'attempts' => (int) ($row->attempts ?? 0),
                'fails' => (int) ($row->fails ?? 0),
            ])
            ->all();

        return view('admin.settings.webhooks.index', [
            'module' => $module,
            'hasAccess' => $hasAccess,
            'endpoints' => $endpoints,
            'availableEvents' => $this->availableEvents(),
            'endpointMetrics' => $endpointMetrics,
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_unless($user, 401);

        return view('admin.settings.webhooks.create', [
            'availableEvents' => $this->availableEvents(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate(['name' => 'required|string|max:100',
            'url' => 'required|url|max:2048',
            'events' => 'required|array|min:1',
            'events.*' => 'string',
            'is_active' => 'nullable|boolean',
            'timeout_seconds' => 'nullable|integer|min:1|max:60',
            'headers_json' => 'nullable|string|max:8000',
        ]);

        app(WebhookUrlGuard::class)->assertSendable((string) $validated['url']);

        $events = array_values(array_unique(array_values($validated['events'] ?? [])));
        $allowed = array_keys($this->availableEvents());
        foreach ($events as $ev) {
            if (!in_array($ev, $allowed, true)) {
                throw ValidationException::withMessages([
                    'events' => ['Geçersiz event seçimi.'],
                ]);
            }
        }
$headersJson = $this->parseHeadersJson((string) ($validated['headers_json'] ?? ''));

        $secretPlain = Str::random(48);

        $endpoint = WebhookEndpoint::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'url' => $validated['url'],
            'secret' => $secretPlain,
            'events' => $events,
            'is_active' => $request->boolean('is_active', true),
            'headers_json' => empty($headersJson) ? null : $headersJson,
            'timeout_seconds' => (int) ($validated['timeout_seconds'] ?? 10),
        ]);

        return redirect()
            ->route('portal.webhooks.edit', $endpoint)
            ->with('created_webhook_secret', $secretPlain)
            ->with('success', 'Webhook endpoint oluşturuldu.');
    }

    public function edit(Request $request, WebhookEndpoint $endpoint): View
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($endpoint->user_id !== $user->id) {
            abort(404);
        }

        return view('admin.settings.webhooks.edit', [
            'endpoint' => $endpoint,
            'availableEvents' => $this->availableEvents(),
        ]);
    }

    public function update(Request $request, WebhookEndpoint $endpoint): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($endpoint->user_id !== $user->id) {
            abort(404);
        }
$validated = $request->validate(['name' => 'required|string|max:100',
            'url' => 'required|url|max:2048',
            'events' => 'required|array|min:1',
            'events.*' => 'string',
            'is_active' => 'nullable|boolean',
            'timeout_seconds' => 'nullable|integer|min:1|max:60',
            'headers_json' => 'nullable|string|max:8000',
        ]);

        app(WebhookUrlGuard::class)->assertSendable((string) $validated['url']);

        $events = array_values(array_unique(array_values($validated['events'] ?? [])));
        $allowed = array_keys($this->availableEvents());
        foreach ($events as $ev) {
            if (!in_array($ev, $allowed, true)) {
                throw ValidationException::withMessages([
                    'events' => ['Geçersiz event seçimi.'],
                ]);
            }
        }
$headersJson = $this->parseHeadersJson((string) ($validated['headers_json'] ?? ''));

        $endpoint->name = $validated['name'];
        $endpoint->url = $validated['url'];
        $endpoint->events = $events;
        $endpoint->is_active = $request->boolean('is_active', false);
        $endpoint->timeout_seconds = (int) ($validated['timeout_seconds'] ?? 10);
        $endpoint->headers_json = empty($headersJson) ? null : $headersJson;
        $endpoint->save();

        return back()->with('success', 'Webhook endpoint güncellendi.');
    }

    public function rotateSecret(Request $request, WebhookEndpoint $endpoint): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($endpoint->user_id !== $user->id) {
            abort(404);
        }
$secretPlain = Str::random(48);
        $endpoint->secret = $secretPlain;
        $endpoint->rotated_at = now();
        $endpoint->save();

        return back()
            ->with('created_webhook_secret', $secretPlain)
            ->with('success', 'Webhook secret yenilendi (bir kere gösterilir).');
    }

    public function test(Request $request, WebhookEndpoint $endpoint, WebhookService $webhooks): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($endpoint->user_id !== $user->id) {
            abort(404);
        }
$webhooks->dispatchEventToEndpoint($user, $endpoint, 'webhook.test', [
            'einvoice' => [
                'id' => null,
                'status' => null,
                'type' => null,
                'invoice_no' => null,
                'issued_at' => null,
                'totals' => [
                    'subtotal' => 0,
                    'tax_total' => 0,
                    'discount_total' => 0,
                    'grand_total' => 0,
                    'currency' => 'TRY',
                ],
                'marketplace' => null,
                'order_no' => null,
                'provider' => null,
                'provider_status' => null,
            ],
            'user' => [
                'id' => $user->id,
            ],
        ]);

        return back()->with('success', 'Test webhook kuyruğa alındı.');
    }

    public function enable(Request $request, WebhookEndpoint $endpoint): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($endpoint->user_id !== $user->id) {
            abort(404);
        }
$endpoint->is_active = true;
        $endpoint->disabled_at = null;
        $endpoint->disabled_reason = null;
        $endpoint->save();

        return back()->with('success', 'Webhook endpoint tekrar aktif edildi.');
    }

    /**
     * @return array<string,string>
     */
    private function parseHeadersJson(string $input): array
    {
        $input = trim($input);
        if ($input === '') {
            return [];
        }
$decoded = json_decode($input, true);
        if (!is_array($decoded)) {
            throw ValidationException::withMessages([
                'headers_json' => ['Headers JSON geçersiz. Örn: {"X-Customer":"abc"}'],
            ]);
        }
$result = [];
        foreach ($decoded as $key => $value) {
            if (!is_string($key) || trim($key) === '') {
                continue;
            }
            if (is_array($value) || is_object($value)) {
                continue;
            }
$result[trim($key)] = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        }

        return $result;
    }
}


