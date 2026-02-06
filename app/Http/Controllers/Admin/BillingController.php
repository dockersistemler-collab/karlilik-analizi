<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingCheckout;
use App\Services\Features\FeatureGate;
use App\Services\Billing\Iyzico\CheckoutFormService;
use App\Services\SystemSettings\SettingsRepository;
use App\Models\BillingSubscription;
use App\Services\Billing\Iyzico\Subscription\IyzicoSubscriptionClient;
use App\Support\SupportUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function plans(Request $request, SettingsRepository $settings, FeatureGate $features): View
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);

        $catalog = $this->loadPlansCatalog($settings, $features);
        $currentPlan = $features->planCodeForTenant($user);
        $highlightFeature = $request->query('feature');

        $subscription = BillingSubscription::query()
            ->where('tenant_id', $user->id)
            ->orderByDesc('created_at')
            ->first();

        return view('admin.billing.plans', [
            'plansCatalog' => $catalog,
            'currentPlanCode' => $currentPlan,
            'featureLabels' => $features->featureLabels(),
            'highlightFeature' => is_string($highlightFeature) ? $highlightFeature : null,
            'subscription' => $subscription,
        ]);
    }

    public function checkout(Request $request, SettingsRepository $settings, FeatureGate $features, CheckoutFormService $checkoutFormService): RedirectResponse
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);

        $catalog = $this->loadPlansCatalog($settings, $features);

        $data = $request->validate(['plan_code' => 'required|string|max:50',
        ]);

        $planCode = strtolower(trim((string) $data['plan_code']));
        if (!array_key_exists($planCode, $catalog)) {
            return redirect()->route('portal.billing.plans')->with('error', 'Plan bulunamadı.');
        }
$checkout = BillingCheckout::create([
            'tenant_id' => $user->id,
            'plan_code' => $planCode,
            'status' => 'pending',
            'provider' => null,
            'provider_session_id' => null,
        ]);

        $iyzicoEnabled = filter_var($settings->get('billing', 'iyzico.enabled', false), FILTER_VALIDATE_BOOLEAN);
        if ($iyzicoEnabled) {
            $tenant = $this->resolveTenantUser($user);
            $result = $checkoutFormService->initializeCheckout($checkout, $user, $tenant, $catalog[$planCode]);

            $checkout->provider = 'iyzico';
            $checkout->provider_session_id = $result->token;
            $checkout->provider_token = $result->token;
            $checkout->checkout_form_content = $result->checkoutFormContent;
            $checkout->save();

            return redirect()->route('portal.billing.iyzico.show', $checkout);
        }

        return redirect()->route('portal.billing.success', ['checkout' => $checkout->id]);
    }

    public function success(Request $request): View
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);

        $checkoutId = (string) $request->query('checkout');
        $checkout = BillingCheckout::query()
            ->where('tenant_id', $user->id)
            ->whereKey($checkoutId)
            ->first();

        if ($checkout && $checkout->status === 'pending' && $checkout->provider !== 'iyzico') {
            $checkout->status = 'completed';
            $checkout->save();

            $tenant = $this->resolveTenantUser($user);
            $tenant->plan_code = $checkout->plan_code;
            $tenant->plan_started_at = now();
            $tenant->plan_expires_at = null;
            $tenant->save();
        }

        return view('admin.billing.success', [
            'checkout' => $checkout,
        ]);
    }

    public function cancel(): View
    {
        return view('admin.billing.cancel');
    }

    public function showIyzico(BillingCheckout $checkout): View
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);

        if ((int) $checkout->tenant_id !== (int) $user->id) {
            abort(403);
        }

        return view('admin.billing.iyzico', [
            'checkout' => $checkout,
        ]);
    }

    public function subscribe(Request $request, SettingsRepository $settings, FeatureGate $features, IyzicoSubscriptionClient $client): RedirectResponse
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);

        $data = $request->validate(['plan_code' => 'required|string|max:50',
        ]);

        $catalog = $this->loadPlansCatalog($settings, $features);
        $planCode = strtolower(trim($data['plan_code']));
        $plan = $catalog[$planCode] ?? null;
        if (!$plan || empty($plan['iyzico']['pricingPlanReferenceCode'])) {
            return redirect()->route('portal.billing.plans')->with('error', 'Iyzico mapping eksik.');
        }
$subscription = BillingSubscription::create([
            'tenant_id' => $user->id,
            'user_id' => $user->id,
            'provider' => 'iyzico',
            'plan_code' => $planCode,
            'status' => 'PENDING',
            'iyzico_pricing_plan_reference_code' => (string) $plan['iyzico']['pricingPlanReferenceCode'],
        ]);

        $payload = $this->buildSubscriptionPayload($user, $plan, $subscription);
        $result = $client->createSubscriptionCheckoutForm($payload);

        $subscription->iyzico_checkout_form_token = (string) $result->getToken();
        $subscription->iyzico_checkout_form_content = (string) $result->getCheckoutFormContent();
        $subscription->status = 'PENDING';
        $subscription->save();

        return redirect()->route('portal.billing.subscription.show', $subscription);
    }

    public function showSubscription(BillingSubscription $subscription): View
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);

        if ((int) $subscription->tenant_id !== (int) $user->id) {
            abort(403);
        }

        return view('admin.billing.iyzico_subscription', [
            'subscription' => $subscription,
        ]);
    }

    public function upgradeSubscription(Request $request, SettingsRepository $settings, FeatureGate $features, IyzicoSubscriptionClient $client): RedirectResponse
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);

        $data = $request->validate(['plan_code' => 'required|string|max:50',
        ]);

        $catalog = $this->loadPlansCatalog($settings, $features);
        $planCode = strtolower(trim($data['plan_code']));
        $plan = $catalog[$planCode] ?? null;
        if (!$plan || empty($plan['iyzico']['pricingPlanReferenceCode'])) {
            return redirect()->route('portal.billing.plans')->with('error', 'Iyzico mapping eksik.');
        }
$subscription = BillingSubscription::query()
            ->where('tenant_id', $user->id)
            ->where('provider', 'iyzico')
            ->orderByDesc('created_at')
            ->first();

        if (!$subscription || !$subscription->iyzico_subscription_reference_code) {
            return redirect()->route('portal.billing.plans')->with('error', 'Aktif abonelik bulunamadı.');
        }
$client->upgradeSubscription($subscription->iyzico_subscription_reference_code, (string) $plan['iyzico']['pricingPlanReferenceCode']);
        $subscription->plan_code = $planCode;
        $subscription->iyzico_pricing_plan_reference_code = (string) $plan['iyzico']['pricingPlanReferenceCode'];
        $subscription->status = $subscription->status ?: 'PENDING';
        $subscription->save();

        return redirect()->route('portal.billing.plans')->with('success', 'Plan değişikliği isteği alındı.');
    }

    public function cancelSubscription(IyzicoSubscriptionClient $client): RedirectResponse
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);

        $subscription = BillingSubscription::query()
            ->where('tenant_id', $user->id)
            ->where('provider', 'iyzico')
            ->orderByDesc('created_at')
            ->first();

        if (!$subscription || !$subscription->iyzico_subscription_reference_code) {
            return redirect()->route('portal.billing.plans')->with('error', 'Aktif abonelik bulunamadı.');
        }
$client->cancelSubscription($subscription->iyzico_subscription_reference_code);
        $subscription->status = 'CANCELED';
        $subscription->canceled_at = now();
        $subscription->save();

        $tenant = $this->resolveTenantUser($user);
        $tenant->plan_code = 'free';
        $tenant->save();

        return redirect()->route('portal.billing.plans')->with('success', 'Abonelik iptal edildi.');
    }

    private function buildSubscriptionPayload(\App\Models\User $user, array $plan, BillingSubscription $subscription): array
    {
        return [
            'callbackUrl' => route('billing.iyzico.subscription.callback'),
            'pricingPlanReferenceCode' => (string) $plan['iyzico']['pricingPlanReferenceCode'],
            'initialStatus' => 'PENDING',
            'customer' => [
                'name' => $user->name ?: 'Musteri',
                'surname' => ' ',
                'identityNumber' => '11111111111',
                'email' => $user->email,
                'gsmNumber' => '+905555555555',
                'billingAddress' => $user->billing_address ?: $user->company_address ?: 'Istanbul',
                'shippingAddress' => $user->company_address ?: 'Istanbul',
                'city' => 'Istanbul',
                'country' => 'Turkey',
                'zipCode' => '34000',
            ],
            'buyer' => [
                'id' => (string) $user->id,
                'name' => $user->name ?: 'Musteri',
                'surname' => ' ',
                'identityNumber' => '11111111111',
                'email' => $user->email,
                'gsmNumber' => '+905555555555',
                'registrationAddress' => $user->company_address ?: 'Istanbul',
                'city' => 'Istanbul',
                'country' => 'Turkey',
                'zipCode' => '34000',
                'ip' => '127.0.0.1',
            ],
            'shippingAddress' => [
                'contactName' => $user->name ?: 'Musteri',
                'city' => 'Istanbul',
                'country' => 'Turkey',
                'address' => $user->company_address ?: 'Istanbul',
                'zipCode' => '34000',
            ],
            'billingAddress' => [
                'contactName' => $user->name ?: 'Musteri',
                'city' => 'Istanbul',
                'country' => 'Turkey',
                'address' => $user->billing_address ?: $user->company_address ?: 'Istanbul',
                'zipCode' => '34000',
            ],
            'paymentCard' => null,
        ];
    }

    private function loadPlansCatalog(SettingsRepository $settings, FeatureGate $features): array
    {
        $raw = $settings->get('billing', 'plans_catalog', null);
        $decoded = null;

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
        } elseif (is_array($raw)) {
            $decoded = $raw;
        }

        if (!is_array($decoded)) {
            $decoded = config('billing.plans_catalog', []);
        }
$allowedFeatures = $features->allFeatures();
        $normalized = [];
        foreach ($decoded as $planCode => $plan) {
            if (!is_string($planCode) || !is_array($plan)) {
                continue;
            }
$code = strtolower(trim($planCode));
            $featuresList = $plan['features'] ?? [];
            if (!is_array($featuresList)) {
                $featuresList = [];
            }
$normalized[$code] = [
                'name' => (string) ($plan['name'] ?? $code),
                'price_monthly' => (int) ($plan['price_monthly'] ?? 0),
                'features' => array_values(array_filter($featuresList, fn ($value) => is_string($value) && in_array($value, $allowedFeatures, true))),
                'recommended' => (bool) ($plan['recommended'] ?? false),
                'contact_sales' => (bool) ($plan['contact_sales'] ?? false),
                'iyzico' => [
                    'productReferenceCode' => (string) ($plan['iyzico']['productReferenceCode'] ?? ''),
                    'pricingPlanReferenceCode' => (string) ($plan['iyzico']['pricingPlanReferenceCode'] ?? ''),
                ],
            ];
        }

        if ($normalized === []) {
            $normalized = config('billing.plans_catalog', []);
        }
$order = ['free', 'pro', 'enterprise'];
        uksort($normalized, function (string $a, string $b) use ($order): int {
            $posA = array_search($a, $order, true);
            $posB = array_search($b, $order, true);
            if ($posA === false && $posB === false) {
                return strcmp($a, $b);
            }
            if ($posA === false) {
                return 1;
            }
            if ($posB === false) {
                return -1;
            }
            return $posA <=> $posB;
        });

        return $normalized;
    }

    private function resolveTenantUser(\App\Models\User $user): \App\Models\User
    {
        if (method_exists($user, 'tenant')) {
            $tenant = $user->tenant;
            if ($tenant instanceof \App\Models\User) {
                return $tenant;
            }
        }

        if (property_exists($user, 'tenant_id') && $user->tenant_id) {
            $tenant = \App\Models\User::query()->find($user->tenant_id);
            if ($tenant) {
                return $tenant;
            }
        }

        return $user;
    }
}


