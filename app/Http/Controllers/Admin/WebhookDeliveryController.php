<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class WebhookDeliveryController extends Controller
{
    public function index(Request $request, WebhookEndpoint $endpoint): View
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($endpoint->user_id !== $user->id) {
            abort(404);
        }
$validated = $request->validate(['status' => 'nullable|string|in:pending,success,failed,retrying,disabled',
            'event' => 'nullable|string|max:120',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $query = WebhookDelivery::query()
            ->where('webhook_endpoint_id', $endpoint->id);

        if (!empty($validated['status'])) {
            $query->where('status', (string) $validated['status']);
        }
        if (!empty($validated['event'])) {
            $query->where('event', (string) $validated['event']);
        }
        if (!empty($validated['from'])) {
            $query->where('created_at', '>=', Carbon::parse((string) $validated['from'])->startOfDay());
        }
        if (!empty($validated['to'])) {
            $query->where('created_at', '<=', Carbon::parse((string) $validated['to'])->endOfDay());
        }
$deliveries = $query
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.settings.webhooks.deliveries', [
            'endpoint' => $endpoint,
            'deliveries' => $deliveries,
            'filters' => $validated,
        ]);
    }

    public function show(Request $request, WebhookDelivery $delivery): View
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($delivery->user_id !== $user->id) {
            abort(404);
        }
$delivery->loadMissing('endpoint');

        return view('admin.settings.webhooks.delivery-show', [
            'delivery' => $delivery,
        ]);
    }
}
