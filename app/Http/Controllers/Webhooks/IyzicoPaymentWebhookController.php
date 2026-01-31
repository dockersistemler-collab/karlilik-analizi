<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\ModulePurchase;
use App\Services\Purchases\ModulePurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IyzicoPaymentWebhookController extends Controller
{
    public function __construct(private readonly ModulePurchaseService $purchases)
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
            data_get($payload, 'provider_payment_id')
            ?? data_get($payload, 'payment_id')
            ?? data_get($payload, 'paymentId')
            ?? data_get($payload, 'payment.id');

        $rawStatus =
            data_get($payload, 'status')
            ?? data_get($payload, 'payment_status')
            ?? data_get($payload, 'payment.status');

        $providerPaymentId = is_string($providerPaymentId) ? trim($providerPaymentId) : null;
        $rawStatus = is_string($rawStatus) ? strtolower(trim($rawStatus)) : null;

        if (!$providerPaymentId || !$rawStatus) {
            return response()->json(['ok' => true, 'ignored' => true], 200);
        }

        $purchase = ModulePurchase::query()
            ->where('provider', 'iyzico')
            ->where('provider_payment_id', $providerPaymentId)
            ->first();

        if (!$purchase) {
            return response()->json(['ok' => true, 'ignored' => true], 200);
        }

        $target = match ($rawStatus) {
            'paid', 'success', 'successful' => 'paid',
            'refunded', 'refund' => 'refunded',
            'cancelled', 'canceled', 'cancel' => 'cancelled',
            default => null,
        };

        if (!$target) {
            return response()->json(['ok' => true, 'ignored' => true], 200);
        }

        if ($purchase->status === $target) {
            return response()->json(['ok' => true, 'idempotent' => true], 200);
        }

        if ($target === 'paid') {
            $this->purchases->markPaid($purchase);
        } elseif ($target === 'refunded') {
            $this->purchases->markRefunded($purchase);
        } elseif ($target === 'cancelled') {
            $this->purchases->markCancelled($purchase);
        }

        return response()->json(['ok' => true], 200);
    }
}

