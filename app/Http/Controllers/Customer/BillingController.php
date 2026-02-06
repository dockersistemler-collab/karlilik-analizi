<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BillingEvent;
use App\Models\BillingCheckout;
use App\Models\BillingSubscription;
use App\Models\BillingSubscriptionEvent;
use App\Services\Billing\Iyzico\Subscription\CardUpdateCheckoutFormService;
use App\Support\SupportUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    public function index(Request $request): View
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);

        $subscription = BillingSubscription::query()
            ->where('tenant_id', $user->id)
            ->orderByDesc('created_at')
            ->first();

        $status = strtoupper((string) ($subscription?->status ?? ''));
        $isPastDue = in_array($status, ['PAST_DUE', 'UNPAID', 'FAILURE', 'FAILED'], true);
        $isCanceled = in_array($status, ['CANCELED', 'CANCELLED'], true);

        $badge = 'unknown';
        if ($status === 'ACTIVE') {
            $badge = 'active';
        } elseif ($isPastDue) {
            $badge = 'past_due';
        } elseif ($isCanceled) {
            $badge = 'canceled';
        }
$nextRetryAt = $subscription?->next_payment_at ?? $subscription?->grace_until;
$failureEvent = BillingEvent::query()
            ->where('tenant_id', $user->id)
            ->whereIn('type', ['iyzico.webhook.failed', 'dunning.retry_failed', 'dunning.retry_attempt'])
            ->orderByDesc('created_at')
            ->first();

        $lastFailureMessage = $this->extractFailureMessage($failureEvent?->payload ?? null);
        $maskedCard = $this->resolveMaskedCard($subscription);

        $cardUpdate = (string) $request->query('card_update', '');
        if ($cardUpdate === 'success') {
            session()->flash('success', 'Kart guncellendi, odeme tekrar denenecek.');
        }
        if ($cardUpdate === 'failed') {
            session()->flash('error', 'Kart guncellenemedi. Lutfen destek ekibine ulasin.');
        }

        return view('customer.billing.index', [
            'subscription' => $subscription,
            'status' => $status,
            'badge' => $badge,
            'isPastDue' => $isPastDue,
            'nextRetryAt' => $nextRetryAt,
            'lastFailureMessage' => $lastFailureMessage,
            'maskedCard' => $maskedCard,
        ]);
    }

    public function cardUpdateForm(Request $request): View
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);

        $checkout = BillingCheckout::query()
            ->where('tenant_id', $user->id)
            ->where('purpose', 'card_update')
            ->orderByDesc('created_at')
            ->first();

        return view('customer.billing.card_update', [
            'checkout' => $checkout,
        ]);
    }

    public function cardUpdateInitialize(
        Request $request,
        CardUpdateCheckoutFormService $service
    ): RedirectResponse {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);

        $subscription = BillingSubscription::query()
            ->where('tenant_id', $user->id)
            ->orderByDesc('created_at')
            ->first();

        if (!$subscription || !$subscription->iyzico_customer_reference_code) {
            return redirect()->route('portal.billing')->with('error', 'Kart guncelleme icin iyzico bilgisi bulunamadi.');
        }
$state = (string) Str::uuid();
        $callbackUrl = route('billing.iyzico.subscription.card-update.callback', ['state' => $state]);

        $result = $service->initialize($callbackUrl,
            (string) $subscription->iyzico_customer_reference_code,
            $subscription->iyzico_subscription_reference_code
        );

        $status = strtolower((string) ($result['status'] ?? ''));
        if ($status !== 'success' || empty($result['token'])) {
            return redirect()->route('portal.billing')
                ->with('error', $result['errorMessage'] ?? 'Kart guncelleme baslatilamadi.');
        }

        BillingCheckout::create([
            'tenant_id' => $user->id,
            'billing_subscription_id' => $subscription->id,
            'plan_code' => $subscription->plan_code ?: 'card_update',
            'purpose' => 'card_update',
            'status' => 'pending',
            'provider' => 'iyzico',
            'provider_session_id' => $state,
            'provider_token' => (string) $result['token'],
            'checkout_form_content' => (string) ($result['checkoutFormContent'] ?? ''),
            'raw_initialize' => $result['raw'] ?? null,
        ]);

        return redirect()->route('portal.billing.card-update');
    }

    public function cardUpdateResult(Request $request): View
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);

        $state = (string) $request->query('state', '');
        $checkout = BillingCheckout::query()
            ->where('tenant_id', $user->id)
            ->where('purpose', 'card_update')
            ->when($state !== '', fn ($q) => $q->where('provider_session_id', $state))
            ->orderByDesc('created_at')
            ->first();

        return view('customer.billing.card_update_result', [
            'checkout' => $checkout,
        ]);
    }

    private function resolveMaskedCard(?BillingSubscription $subscription): ?string
    {
        if (!$subscription) {
            return null;
        }
$event = BillingSubscriptionEvent::query()
            ->where('subscription_id', $subscription->id)
            ->orderByDesc('received_at')
            ->first();

        $payload = $event?->payload ?? [];
        if (!is_array($payload)) {
            $payload = [];
        }
$lastFour = $payload['lastFourDigits'] ?? $payload['last_four_digits'] ?? $payload['cardLastFour'] ?? null;
        if (is_string($lastFour) && trim($lastFour) !== '') {
            return '**** **** **** '.trim($lastFour);
        }
$bin = $payload['binNumber'] ?? $payload['bin'] ?? null;
        if (is_string($bin) && trim($bin) !== '') {
            return trim($bin).'******';
        }

        return null;
    }

    private function extractFailureMessage(mixed $payload): ?string
    {
        if (!is_array($payload)) {
            return null;
        }
$candidates = [
            $payload['error_message'] ?? null,
            $payload['errorMessage'] ?? null,
            $payload['error'] ?? null,
            $payload['message'] ?? null,
            $payload['failure_message'] ?? null,
        ];

        foreach ($candidates as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}
