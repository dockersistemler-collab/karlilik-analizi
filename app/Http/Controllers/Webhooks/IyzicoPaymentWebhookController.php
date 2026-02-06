<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\ModulePurchase;
use App\Services\BillingEventLogger;
use App\Services\Purchases\ModulePurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IyzicoPaymentWebhookController extends Controller
{
    public function __construct(
        private readonly ModulePurchaseService $purchases,
        private readonly BillingEventLogger $events
    )
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        // Security placeholder (signature verification to be added later).
        $expected = config('services.iyzico.webhook_secret');
        if (is_string($expected) && $expected !== '') {
            $provided = (string) $request->header('x-webhook-secret', '');
            if (!hash_equals($expected, $provided)) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        }
$payload = $request->all();

        $providerPaymentId =
            data_get($payload, 'provider_payment_id') ??
              data_get($payload, 'paymentId') ??
              data_get($payload, 'paymentConversationId') ??
              data_get($payload, 'payment_id') ??
              data_get($payload, 'payment.id');

        $rawStatus =
            data_get($payload, 'status') ??
              data_get($payload, 'payment_status') ??
              data_get($payload, 'payment.status');

        $providerPaymentId = is_string($providerPaymentId) ? trim($providerPaymentId) : null;
        $rawStatus = is_string($rawStatus) ? strtolower(trim($rawStatus)) : null;

        if (!$providerPaymentId || !$rawStatus) {
            Log::info('iyzico.webhook.ignored.missing_fields', [
                'has_provider_payment_id' => (bool) $providerPaymentId,
                'has_status' => (bool) $rawStatus,
                'payload_keys' => array_keys($payload),
            ]);
            $this->events->record(['tenant_id' => null,
                'type' => 'iyzico.webhook.failed',
                'status' => 'missing_fields',
                'provider' => 'iyzico',
                'payload' => $payload,
            ]);
            return response()->json(['ok' => true, 'ignored' => true], 200);
        }
$purchase = ModulePurchase::query()
            ->where('provider', 'iyzico')
            ->where('provider_payment_id', $providerPaymentId)
            ->first();

        if (!$purchase) {
            Log::warning('iyzico.webhook.purchase_not_found', [
                'provider_payment_id' => $providerPaymentId,
                'status' => $rawStatus,
            ]);
            $this->events->record(['tenant_id' => null,
                'type' => 'iyzico.webhook.failed',
                'status' => 'purchase_not_found',
                'provider' => 'iyzico',
                'payload' => $payload,
            ]);
            return response()->json(['ok' => true, 'ignored' => true], 200);
        }
$target = match ($rawStatus) {
            'paid', 'success', 'successful', 'succeeded' => 'paid',
            'failure', 'failed' => 'cancelled',
            'refunded', 'refund' => 'refunded',
            'cancelled', 'canceled', 'cancel' => 'cancelled',
            default => null,
        };

        if (!$target) {
            Log::info('iyzico.webhook.ignored.unknown_status', [
                'provider_payment_id' => $providerPaymentId,
                'status' => $rawStatus,
            ]);
            $this->events->record(['tenant_id' => (int) $purchase->user_id,
                'user_id' => $purchase->user_id,
                'type' => 'iyzico.webhook.failed',
                'status' => 'unknown_status',
                'provider' => 'iyzico',
                'payload' => $payload,
            ]);
            return response()->json(['ok' => true, 'ignored' => true], 200);
        }

        if ($purchase->status === $target) {
            return response()->json(['ok' => true, 'idempotent' => true], 200);
        }

        if ($target === 'paid') {
            $this->purchases->markPaid($purchase, ['iyzico_webhook' => $payload]);
        } elseif ($target === 'refunded') {
            $this->purchases->markRefunded($purchase);
        } elseif ($target === 'cancelled') {
            $this->purchases->markCancelled($purchase);
        }
$this->events->record([
            'tenant_id' => (int) $purchase->user_id,
            'user_id' => $purchase->user_id,
            'type' => $target === 'paid' ? 'iyzico.webhook.succeeded' : 'iyzico.webhook.failed',
            'status' => $target,
            'provider' => 'iyzico',
            'payload' => $payload,
        ]);

        return response()->json(['ok' => true], 200);
    }
}
