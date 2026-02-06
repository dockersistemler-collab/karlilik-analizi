<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Events\PaymentFailed;
use App\Events\PaymentSucceeded;
use App\Models\ModulePurchase;
use App\Services\Payments\IyzicoClient;
use App\Services\Purchases\ModulePurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class IyzicoCheckoutCallbackController extends Controller
{
    public function __construct(
        private readonly IyzicoClient $iyzico,
        private readonly ModulePurchaseService $purchases,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $token = trim((string) $request->input('token', ''));
        if ($token === '') {
            abort(400, 'Token is required.');
        }
$retrieve = $this->iyzico->retrieveCheckoutForm($token);

        $status = strtoupper((string) ($retrieve['status'] ?? ''));
        $conversationId = (string) ($retrieve['conversationId'] ?? '');

        $purchaseId = $this->parsePurchaseIdStrict($conversationId);
        if (!$purchaseId) {
            Log::warning('iyzico.callback.invalid_conversation_id', [
                'conversationId' => $conversationId,
                'token' => $token,
            ]);
            abort(400, 'Invalid conversationId.');
        }
$purchase = ModulePurchase::query()
            ->whereKey($purchaseId)
            ->where('provider', 'iyzico')
            ->first();

        if (!$purchase) {
            Log::warning('iyzico.callback.purchase_not_found', [
                'conversationId' => $conversationId,
                'token' => $token,
                'status' => $status,
            ]);
            abort(404);
        }

        if (!in_array($purchase->status, ['pending', 'paid'], true)) {
            abort(404);
        }
$paymentId = $this->stringOrNull($retrieve['paymentId'] ?? null);
        $paymentConversationId = $this->stringOrNull($retrieve['paymentConversationId'] ?? null);
        $providerPaymentId = $paymentId ?? $paymentConversationId;

        if ($providerPaymentId && $purchase->provider_payment_id && $purchase->provider_payment_id !== $providerPaymentId) {
            Log::warning('iyzico.callback.payment_id_mismatch', [
                'purchase_id' => $purchase->id,
                'purchase_provider_payment_id' => $purchase->provider_payment_id,
                'retrieve_provider_payment_id' => $providerPaymentId,
            ]);
            abort(409);
        }

        if ($providerPaymentId && !$purchase->provider_payment_id) {
            $purchase->provider_payment_id = $providerPaymentId;
            $purchase->save();
        }

        if ($status === 'SUCCESS') {
            $this->purchases->markPaid($purchase,
                ['iyzico_retrieve' => $retrieve['raw'] ?? $retrieve],
                $providerPaymentId,
            );
            event(new PaymentSucceeded(
                $purchase->user_id,
                null,
                null,
                $purchase->amount !== null ? (string) $purchase->amount : null,
                $purchase->currency,
                'iyzico',
                $providerPaymentId,
                now()->toDateTimeString()
            ));

            return $this->redirectSuccess($purchase);
        }

        if ($purchase->status === 'pending') {
            $purchase->status = 'cancelled';
            $purchase->meta = array_merge(is_array($purchase->meta) ? $purchase->meta : [], [
                'iyzico_retrieve' => $retrieve['raw'] ?? $retrieve,
            ]);
            $purchase->save();
        }

        event(new PaymentFailed(
            $purchase->user_id,
            null,
            null,
            $purchase->amount !== null ? (string) $purchase->amount : null,
            $purchase->currency,
            'iyzico',
            $this->stringOrNull($retrieve['errorCode'] ?? null),
            (string) ($retrieve['errorMessage'] ?? 'Bilinmeyen hata'),
            now()->toDateTimeString()
        ));

        return $this->redirectFailure((string) ($retrieve['errorMessage'] ?? 'Bilinmeyen hata'));
    }

    private function redirectSuccess(ModulePurchase $purchase): RedirectResponse
    {
        $purchase->loadMissing('module');

        if ($purchase->module?->code === 'feature.einvoice_api') {
            return redirect()
                ->route('portal.settings.api')
                ->with('success', 'Ödeme başarılı. API erişiminiz aktif edildi.');
        }

        return redirect()
            ->route('portal.addons.index')
            ->with('success', 'Ödeme başarılı. Modülünüz aktif edildi.');
    }

    private function redirectFailure(string $message): RedirectResponse
    {
        return redirect()
            ->route('portal.addons.index')
            ->with('error', 'Ödeme başarısız: '.$message);
    }

    private function parsePurchaseIdStrict(string $conversationId): ?int
    {
        if (preg_match('/^purchase:(\d+)$/', trim($conversationId), $m) !== 1) {
            return null;
        }

        return (int) $m[1];
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
$value = trim($value);
        return $value !== '' ? $value : null;
    }
}


