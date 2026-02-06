<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModulePurchase;
use App\Services\Payments\IyzicoClient;
use App\Services\Purchases\ModulePurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Iyzipay\Model\Address;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\Buyer;
use Iyzipay\Model\Currency;
use Iyzipay\Model\Locale;
use Iyzipay\Model\PaymentGroup;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MyModulesController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $tz = 'Europe/Istanbul';
        $now = Carbon::now($tz)->startOfDay();

        $userModules = $user->userModules()
            ->with('module')
            ->orderByRaw('CASE WHEN ends_at IS NULL THEN 1 ELSE 0 END, ends_at ASC')
            ->get()
            ->map(function ($userModule) use ($now, $tz) {
                $endsAt = $userModule->ends_at?->copy()->timezone($tz)->startOfDay();
$daysLeft = $endsAt ? $now->diffInDays($endsAt, false) : null;

                $userModule->days_left = $daysLeft;
                $userModule->ends_at_local = $userModule->ends_at?->copy()->timezone($tz);
                return $userModule;
            });

        return view('admin.modules.my-modules', compact('userModules'));
    }

    public function renew(Request $request, Module $module, IyzicoClient $iyzico, ModulePurchaseService $purchases): Response
    {
        $user = Auth::user();
        abort_unless($user, 403);

        if (!$module->is_active) {
            abort(404);
        }
$validated = $request->validate(['period' => 'nullable|in:monthly,yearly',
        ]);

        $period = (string) ($validated['period'] ?? 'monthly');
        $currency = 'TRY';

        $prices = config('modules.prices', []);
        $amount = $prices[$module->code][$period] ?? null;
        if (!is_numeric($amount) || (float) $amount <= 0) {
            throw ValidationException::withMessages([
                'period' => 'Bu modül için fiyat tanımı bulunamadı.',
            ]);
        }
$amount = (float) $amount;

        $referer = (string) $request->headers->get('referer', '');
        $redirectRoute = str_contains($referer, '/portal/settings/api') || $module->code === 'feature.einvoice_api'
            ? 'portal.settings.api'
            : 'portal.modules.mine';

        if (config('payments.mode') === 'fake') {
            $purchase = DB::transaction(function () use ($user, $module, $amount, $currency, $period) {
                return ModulePurchase::create([
                    'user_id' => $user->id,
                    'module_id' => $module->id,
                    'provider' => 'fake',
                    'provider_payment_id' => null,
                    'amount' => $amount,
                    'currency' => $currency,
                    'period' => $period,
                    'status' => 'pending',
                    'starts_at' => null,
                    'ends_at' => null,
                    'meta' => [
                        'action' => 'renew',
                        'source' => 'my_modules',
                        'fake' => true,
                    ],
                ]);
            });

            $purchases->markPaid($purchase, ['fake' => true], "fake:{$purchase->id}");

            return redirect()
                ->route($redirectRoute)
                ->with('success', 'Ã–deme baÅŸarıyla tamamlandı (Fake Payments Mode).');
        }

        if (!config('payments.iyzico_enabled')) {
            return redirect()
                ->back()
                ->with('error', 'Ã–deme altyapısı yapılandırılmamıÅŸ (Iyzico API bilgileri eksik).');
        }
$purchase = DB::transaction(function () use ($user, $module, $amount, $currency, $period) {
            return ModulePurchase::create([
                'user_id' => $user->id,
                'module_id' => $module->id,
                'provider' => 'iyzico',
                'provider_payment_id' => null,
                'amount' => $amount,
                'currency' => $currency,
                'period' => $period,
                'status' => 'pending',
                'starts_at' => null,
                'ends_at' => null,
                'meta' => [
                    'action' => 'renew',
                    'source' => 'my_modules',
                ],
            ]);
        });

        $conversationId = "purchase:{$purchase->id}";
        $callbackUrl = route('iyzico.callback');

        Log::info('iyzico.initialize.start', [
            'user_id' => $user->id,
            'purchase_id' => $purchase->id,
            'module_code' => $module->code,
            'period' => $period,
            'amount' => $amount,
            'currency' => $currency,
        ]);

        [$firstName, $lastName] = $this->splitName((string) ($user->billing_name ?: $user->name));
        $email = (string) ($user->billing_email ?: $user->email);
        $addressLine = (string) ($user->billing_address ?: $user->company_address ?: 'N/A');
        $gsmNumber = (string) ($user->company_phone ?: '+905350000000');

        $buyer = new Buyer();
        $buyer->setId((string) $user->id);
        $buyer->setName($firstName);
        $buyer->setSurname($lastName);
        $buyer->setGsmNumber($gsmNumber);
        $buyer->setEmail($email);
        $buyer->setIdentityNumber('11111111111');
        $buyer->setRegistrationAddress($addressLine);
        $buyer->setIp((string) $request->ip());
        $buyer->setCity('Istanbul');
        $buyer->setCountry('Turkey');
        $buyer->setZipCode('34000');

        $billing = new Address();
        $billing->setContactName(trim("{$firstName} {$lastName}"));
        $billing->setCity('Istanbul');
        $billing->setCountry('Turkey');
        $billing->setAddress($addressLine);
        $billing->setZipCode('34000');

        $shipping = new Address();
        $shipping->setContactName(trim("{$firstName} {$lastName}"));
        $shipping->setCity('Istanbul');
        $shipping->setCountry('Turkey');
        $shipping->setAddress($addressLine);
        $shipping->setZipCode('34000');

        $basketItem = new BasketItem();
        $basketItem->setId("module:{$module->id}");
        $basketItem->setName($module->name);
        $basketItem->setCategory1($module->type);
        $basketItem->setItemType(BasketItemType::VIRTUAL);
        $basketItem->setPrice(number_format($amount, 2, '.', ''));

        try {
            $result = $iyzico->initializeCheckoutForm(['locale' => Locale::TR,
                'conversationId' => $conversationId,
                'price' => number_format($amount, 2, '.', ''),
                'paidPrice' => number_format($amount, 2, '.', ''),
                'currency' => Currency::TL,
                'basketId' => (string) $purchase->id,
                'paymentGroup' => PaymentGroup::PRODUCT,
                'callbackUrl' => $callbackUrl,
                'buyer' => $buyer,
                'shippingAddress' => $shipping,
                'billingAddress' => $billing,
                'basketItems' => [$basketItem],
            ]);
        } catch (Throwable $e) {
            Log::error('iyzico.initialize.exception', [
                'user_id' => $user->id,
                'purchase_id' => $purchase->id,
                'module_code' => $module->code,
                'period' => $period,
                'amount' => $amount,
                'currency' => $currency,
                'message' => $e->getMessage(),
            ]);

            $purchase->status = 'cancelled';
            $purchase->meta = array_merge(is_array($purchase->meta) ? $purchase->meta : [], [
                'iyzico_initialize_exception' => $e->getMessage(),
            ]);
            $purchase->save();

            return redirect()
                ->route($redirectRoute)
                ->with('error', 'Ã–deme baÅŸlatılamadı: '.$e->getMessage());
        }

        Log::info('iyzico.initialize.result', [
            'user_id' => $user->id,
            'purchase_id' => $purchase->id,
            'module_code' => $module->code,
            'period' => $period,
            'amount' => $amount,
            'currency' => $currency,
            'status' => $result['status'] ?? null,
            'errorCode' => $result['errorCode'] ?? null,
            'errorMessage' => $result['errorMessage'] ?? null,
            'token' => $result['token'] ?? null,
        ]);

        $purchase->meta = array_merge(is_array($purchase->meta) ? $purchase->meta : [], [
            'conversation_id' => $conversationId,
            'iyzico_initialize' => $result['raw'] ?? null,
            'iyzico_token' => $result['token'] ?? null,
            'period' => $period,
            'amount' => $amount,
            'currency' => $currency,
        ]);
        $purchase->save();

        if (strtoupper((string) ($result['status'] ?? '')) !== 'SUCCESS') {
            $purchase->status = 'cancelled';
            $purchase->save();

            return redirect()
                ->route($redirectRoute)
                ->with('error', 'Ã–deme baÅŸlatılamadı: '.((string) ($result['errorMessage'] ?? 'Bilinmeyen hata')));
        }

        return response()->view('admin.payments.iyzico-checkout', [
            'purchase' => $purchase,
            'module' => $module,
            'checkoutFormContent' => (string) ($result['checkoutFormContent'] ?? ''),
        ]);
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitName(string $fullName): array
    {
        $fullName = trim($fullName);
        if ($fullName === '') {
            return ['MüÅŸteri', ''];
        }
$parts = preg_split('/\s+/', $fullName) ?: [];
        $first = (string) array_shift($parts);
        $last = trim(implode(' ', $parts));
        return [$first, $last !== '' ? $last : $first];
    }
}


