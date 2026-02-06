<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\NotificationAuditLog;
use App\Models\User;
use App\Models\BillingSubscription;
use App\Models\BillingSubscriptionEvent;
use App\Models\BillingCheckout;
use App\Enums\NotificationSource;
use App\Enums\NotificationType;
use App\Services\BillingEventLogger;
use App\Services\Billing\Iyzico\CheckoutStatusService;
use App\Services\Billing\Iyzico\Subscription\IyzicoSubscriptionClient;
use App\Services\Notifications\NotificationService;
use App\Services\SystemSettings\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class IyzicoController extends Controller
{
    public function callback(Request $request, CheckoutStatusService $statusService): RedirectResponse
    {
        $token = (string) $request->input('token', $request->query('token', ''));
        if ($token === '') {
            return redirect()->route('portal.billing.cancel');
        }
$checkout = BillingCheckout::query()
            ->where('provider', 'iyzico')
            ->where(function ($query) use ($token) {
                $query->where('provider_session_id', $token)
                    ->orWhere('provider_token', $token);
            })
            ->first();

        if (!$checkout) {
            return redirect()->route('portal.billing.cancel');
        }
$checkout->raw_callback = $request->all();
        $checkout->save();

        $status = $statusService->retrieve($token);
        if ($this->isSuccess($status)) {
            $this->completeCheckout($checkout);

            return redirect()->route('portal.billing.success', ['checkout' => $checkout->id]);
        }
$checkout->status = 'canceled';
        $checkout->save();

        return redirect()->route('portal.billing.cancel', ['checkout' => $checkout->id]);
    }

    public function subscriptionCallback(
        Request $request,
        IyzicoSubscriptionClient $client,
        SettingsRepository $settings,
        NotificationService $notifications,
        BillingEventLogger $events
    ): RedirectResponse
    {
        $token = (string) $request->input('token', $request->query('token', ''));
        if ($token === '') {
            return redirect()->route('portal.billing.cancel');
        }
$subscription = BillingSubscription::query()
            ->where('iyzico_checkout_form_token', $token)
            ->first();

        if (!$subscription) {
            return redirect()->route('portal.billing.cancel');
        }

        if (!$subscription->iyzico_subscription_reference_code) {
            return redirect()->route('portal.billing.plans')
                ->with('info', 'Abonelik dogrulamasi webhook ile tamamlanacak.');
        }
$result = $client->retrieveSubscription($subscription->iyzico_subscription_reference_code);
        $status = (string) $result->getSubscriptionStatus();
        $subscription->status = $status;
        $subscription->started_at = $this->parseDate($result->getStartDate());
        $subscription->last_payment_at = null;
        $subscription->next_payment_at = null;
        $subscription->save();

        $this->applyDunningStatus($subscription, $status, $settings, $notifications, $events);

        return redirect()->route('portal.billing.plans')->with('success', 'Abonelik dogrulandi.');
    }

    public function webhook(
        Request $request,
        SettingsRepository $settings,
        CheckoutStatusService $statusService,
        IyzicoSubscriptionClient $subscriptionClient,
        NotificationService $notifications,
        BillingEventLogger $events
    ): Response
    {
        $secret = (string) $settings->get('billing', 'iyzico.webhook_secret', '');
        if ($secret === '') {
            return response('Webhook secret required', 400);
        }
$signature = (string) $request->header('X-IYZ-SIGNATURE-V3', '');
        $payload = (string) $request->getContent();
        $expected = hash_hmac('sha256', $payload, $secret);
        if (!hash_equals($expected, $signature)) {
            $fallbackPayload = json_encode($request->all());
            $fallback = hash_hmac('sha256', (string) $fallbackPayload, $secret);
            if (!hash_equals($fallback, $signature)) {
                return response('Invalid signature', 401);
            }
        }
$data = $request->all();
        $subscriptionRef = $this->resolveSubscriptionReferenceFromPayload($data);
        if ($subscriptionRef) {
            return $this->handleSubscriptionWebhook($subscriptionRef, $data, $subscriptionClient, $settings, $notifications, $events);
        }
$token = $this->resolveTokenFromPayload($data);
        if (!$token) {
            return response('Token missing', 400);
        }
$checkout = BillingCheckout::query()
            ->where('provider', 'iyzico')
            ->where(function ($query) use ($token) {
                $query->where('provider_session_id', $token)
                    ->orWhere('provider_token', $token);
            })
            ->first();

        if (!$checkout) {
            return response('Checkout not found', 404);
        }
$checkout->raw_webhook = $data;
        $checkout->save();

        if ($checkout->status === 'completed') {
            return response('OK', 200);
        }
$status = $statusService->retrieve($token);
        if ($this->isSuccess($status)) {
            $this->completeCheckout($checkout);
            return response('OK', 200);
        }

        return response('OK', 200);
    }

    public function cardUpdateCallback(
        Request $request,
        BillingEventLogger $events
    ): Response {
        $state = (string) $request->input('state', $request->query('state', ''));
        $token = (string) ($request->input('token', $request->query('token', '')) ?? '');

        if ($state === '' || $token === '') {
            Log::warning('iyzico.card_update.missing_state_or_token', ['payload' => $request->all()]);
            $events->record(['tenant_id' => null,
                'user_id' => null,
                'subscription_id' => null,
                'type' => 'card_update.callback_unknown_token',
                'status' => 'ignored',
                'provider' => 'iyzico',
                'payload' => $request->all(),
            ]);
            return response('IGNORED', 200);
        }
$checkout = BillingCheckout::query()
            ->where('purpose', 'card_update')
            ->where('provider_token', $token)
            ->first();

        if (!$checkout || $checkout->provider_session_id !== $state) {
            Log::warning('iyzico.card_update.token_state_mismatch', [
                'state' => $state,
                'token' => $token,
            ]);
            $events->record(['tenant_id' => null,
                'user_id' => null,
                'subscription_id' => null,
                'type' => 'card_update.callback_unknown_token',
                'status' => 'ignored',
                'provider' => 'iyzico',
                'payload' => [
                    'state' => $state,
                    'token' => $token,
                    'raw' => $request->all(),
                ],
            ]);
            return response('IGNORED', 200);
        }

        if (in_array($checkout->status, ['completed', 'failed'], true)) {
            $events->record(['tenant_id' => null,
                'user_id' => null,
                'subscription_id' => null,
                'type' => 'card_update.callback_duplicate',
                'status' => 'ignored',
                'provider' => 'iyzico',
                'payload' => [
                    'state' => $state,
                    'token' => $token,
                    'raw' => $request->all(),
                ],
            ]);
            return response('IGNORED', 200);
        }
$checkout->raw_callback = $request->all();

        $status = strtolower((string) ($request->input('status', $request->query('status', '')) ?? ''));
        $paymentStatus = strtoupper((string) ($request->input('paymentStatus', $request->query('paymentStatus', '')) ?? ''));

        $success = $status === 'success' || $paymentStatus === 'SUCCESS';
        $checkout->status = $success ? 'completed' : 'failed';
        $checkout->completed_at = $success ? now() : null;
        $checkout->save();

        $events->record(['tenant_id' => $checkout->tenant_id,
            'user_id' => $checkout->tenant_id,
            'subscription_id' => $checkout->billing_subscription_id,
            'type' => $success ? 'card_update.succeeded' : 'card_update.failed',
            'status' => $success ? 'SUCCESS' : ($status ?: 'FAILED'),
            'provider' => 'iyzico',
            'payload' => [
                'state' => $state,
                'token' => $token ?: $checkout->provider_token,
                'raw' => $request->all(),
            ],
        ]);

        $redirect = route('portal.billing.card-update.result', [
            'state' => $state,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['status' => $success ? 'success' : 'failed']);
        }

        return redirect($redirect)
            ->with($success ? 'success' : 'error', $success
                ? 'Kart guncellendi, odeme tekrar denenecek.'
                : 'Kart guncellenemedi.'
            );
    }

    private function handleSubscriptionWebhook(
        string $subscriptionRef,
        array $payload,
        IyzicoSubscriptionClient $client,
        SettingsRepository $settings,
        NotificationService $notifications,
        BillingEventLogger $events
    ): Response
    {
        $subscription = BillingSubscription::query()
            ->where('iyzico_subscription_reference_code', $subscriptionRef)
            ->first();

        if (!$subscription) {
            return response('Subscription not found', 404);
        }
$eventId = $payload['eventId'] ?? $payload['event_id'] ?? null;
        $eventType = (string) ($payload['eventType'] ?? $payload['event_type'] ?? 'subscription_event');
        $eventHash = hash('sha256', json_encode($payload));

        $existing = BillingSubscriptionEvent::query()
            ->where(function ($query) use ($eventId, $eventHash) {
                if ($eventId) {
                    $query->where('provider_event_id', $eventId);
                } else {
                    $query->where('event_hash', $eventHash);
                }
            })
            ->first();

        if ($existing) {
            return response('OK', 200);
        }

        BillingSubscriptionEvent::create([
            'subscription_id' => $subscription->id,
            'provider_event_id' => is_string($eventId) ? $eventId : null,
            'event_type' => $eventType,
            'event_hash' => $eventHash,
            'payload' => $payload,
            'received_at' => now(),
        ]);

        $details = $client->retrieveSubscription($subscriptionRef);
        $status = (string) $details->getSubscriptionStatus();
        $subscription->status = $status;
        $subscription->iyzico_pricing_plan_reference_code = (string) $details->getPricingPlanReferenceCode();
        $subscription->started_at = $this->parseDate($details->getStartDate());
        $subscription->last_payment_at = null;
        $subscription->next_payment_at = null;
        if (in_array(strtoupper($status), ['CANCELED', 'CANCELLED'], true)) {
            $subscription->canceled_at = now();
        }
$subscription->save();

        $this->applyDunningStatus($subscription, $status, $settings, $notifications, $events);

        return response('OK', 200);
    }

    private function applyDunningStatus(
        BillingSubscription $subscription,
        string $status,
        SettingsRepository $settings,
        NotificationService $notifications,
        BillingEventLogger $events
    ): void {
        $tenant = User::query()->find($subscription->tenant_id);
        if (!$tenant) {
            return;
        }
$normalized = strtoupper($status);
        $now = now();

        if ($normalized === 'ACTIVE') {
            $subscription->past_due_since = null;
            $subscription->grace_until = null;
            $subscription->last_dunning_sent_at = null;
            $subscription->save();

            $tenant->plan_code = $subscription->plan_code;
            $tenant->save();

            $events->record(['tenant_id' => $tenant->id,
                'user_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'type' => 'iyzico.webhook.succeeded',
                'status' => $normalized,
                'provider' => 'iyzico',
                'payload' => ['source' => 'subscription'],
            ]);
            return;
        }

        if (in_array($normalized, ['CANCELED', 'CANCELLED'], true)) {
            $subscription->past_due_since = null;
            $subscription->grace_until = null;
            $subscription->last_dunning_sent_at = null;
            $subscription->save();

            $tenant->plan_code = 'free';
            $tenant->save();

            $events->record(['tenant_id' => $tenant->id,
                'user_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'type' => 'iyzico.webhook.failed',
                'status' => $normalized,
                'provider' => 'iyzico',
                'payload' => ['source' => 'subscription'],
            ]);
            return;
        }

        if (!$this->isPastDueStatus($normalized)) {
            return;
        }
$changed = false;
        if (!$subscription->past_due_since) {
            $subscription->past_due_since = $now;
            $changed = true;
        }
        if (!$subscription->grace_until) {
            $graceDays = (int) $settings->get('billing', 'dunning.grace_days', 3);
            $subscription->grace_until = $now->copy()->addDays(max(0, $graceDays));
            $changed = true;
        }
        if ($changed) {
            $subscription->save();

            $notifications->notifyUser($tenant, [
                'tenant_id' => $tenant->id,
                'user_id' => $tenant->id,
                'source' => NotificationSource::System->value,
                'type' => NotificationType::Operational->value,
                'title' => 'Odeme alinmadi',
                'body' => 'Odeme alinamadigi icin grace suresi baslatildi.',
                'dedupe_key' => "subscription:{$tenant->id}:past_due",
                'group_key' => "subscription:{$tenant->id}",
                'dedupe_window_minutes' => 1440,
                'data' => [
                    'subscription_id' => $subscription->id,
                    'status' => $normalized,
                    'grace_until' => $subscription->grace_until?->toIso8601String(),
                ],
            ]);

            $events->record(['tenant_id' => $tenant->id,
                'user_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'type' => 'iyzico.webhook.failed',
                'status' => $normalized,
                'provider' => 'iyzico',
                'payload' => ['source' => 'subscription'],
            ]);

            $events->record(['tenant_id' => $tenant->id,
                'user_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'type' => 'dunning.retry_scheduled',
                'status' => $normalized,
                'provider' => 'iyzico',
                'payload' => [
                    'past_due_since' => $subscription->past_due_since?->toIso8601String(), 'grace_until' => $subscription->grace_until?->toIso8601String(),
                ],
            ]);
        }
    }

    private function isPastDueStatus(string $status): bool
    {
        return in_array($status, ['UNPAID', 'PAST_DUE', 'FAILURE', 'FAILED'], true);
    }

    private function resolveTokenFromPayload(array $data): ?string
    {
        $candidates = [
            $data['token'] ?? null,
            $data['checkoutFormToken'] ?? null,
            $data['paymentToken'] ?? null,
            $data['basketId'] ?? null,
        ];

        foreach ($candidates as $value) {
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function resolveSubscriptionReferenceFromPayload(array $data): ?string
    {
        $candidates = [
            $data['subscriptionReferenceCode'] ?? null,
            $data['subscription_reference_code'] ?? null,
            $data['subscriptionRefCode'] ?? null,
        ];

        foreach ($candidates as $value) {
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function parseDate(?string $value): ?\Illuminate\Support\Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function isSuccess(\Iyzipay\Model\CheckoutForm $status): bool
    {
        $apiStatus = strtolower((string) $status->getStatus());
        $paymentStatus = strtoupper((string) $status->getPaymentStatus());
        $paidPrice = (float) $status->getPaidPrice();

        return $apiStatus === 'success' && $paymentStatus === 'SUCCESS' && $paidPrice > 0;
    }

    private function completeCheckout(BillingCheckout $checkout): void
    {
        if ($checkout->status === 'completed') {
            return;
        }
$checkout->status = 'completed';
        $checkout->completed_at = now();
        $checkout->save();

        $tenant = User::query()->find($checkout->tenant_id);
        if ($tenant) {
            $tenant->plan_code = $checkout->plan_code;
            $tenant->plan_started_at = now();
            $tenant->plan_expires_at = null;
            $tenant->save();
        }

        NotificationAuditLog::create([
            'tenant_id' => $checkout->tenant_id,
            'actor_user_id' => null,
            'target_user_id' => $checkout->tenant_id,
            'action' => 'billing_payment_completed',
            'reason' => null,
            'ip' => null,
            'user_agent' => null,
            'meta' => [
                'checkout_id' => $checkout->id,
                'plan_code' => $checkout->plan_code,
            ],
        ]);
    }
}


