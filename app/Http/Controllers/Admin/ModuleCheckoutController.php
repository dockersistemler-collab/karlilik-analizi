<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModulePurchase;
use App\Services\Payments\IyzicoClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Iyzipay\Model\Address;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\Buyer;
use Iyzipay\Model\Currency;
use Iyzipay\Model\Locale;
use Iyzipay\Model\PaymentGroup;

class ModuleCheckoutController extends Controller
{
    public function buy(Request $request, Module $module, IyzicoClient $iyzico): View|RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $validated = $request->validate(['amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'period' => 'required|in:monthly,yearly,one_time',
        ]);

        $amount = (float) $validated['amount'];
        $currency = strtoupper((string) ($validated['currency'] ?? 'TRY'));
        $period = (string) $validated['period'];

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
                    'purchase_id' => null, // filled after create
                ],
            ]);
        });

        $conversationId = "purchase:{$purchase->id}";
        $callbackUrl = route('iyzico.callback');

        $purchase->meta = array_merge(is_array($purchase->meta) ? $purchase->meta : [], [
            'purchase_id' => $purchase->id,
            'conversation_id' => $conversationId,
        ]);
        $purchase->save();

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

        $result = $iyzico->initializeCheckoutForm(['locale' => Locale::TR,
            'conversationId' => $conversationId,
            'price' => number_format($amount, 2, '.', ''),
            'paidPrice' => number_format($amount, 2, '.', ''),
            'currency' => $currency === 'TRY' ? Currency::TL : $currency,
            'basketId' => (string) $purchase->id,
            'paymentGroup' => PaymentGroup::PRODUCT,
            'callbackUrl' => $callbackUrl,
            'buyer' => $buyer,
            'shippingAddress' => $shipping,
            'billingAddress' => $billing,
            'basketItems' => [$basketItem],
        ]);

        $purchase->meta = array_merge(is_array($purchase->meta) ? $purchase->meta : [], [
            'iyzico_initialize' => $result['raw'] ?? null,
            'iyzico_token' => $result['token'] ?? null,
        ]);
        $purchase->save();

        if (strtoupper((string) ($result['status'] ?? '')) !== 'SUCCESS') {
            $purchase->status = 'cancelled';
            $purchase->save();

            return back()->with('error', 'Ödeme başlatılamadı: '.((string) ($result['errorMessage'] ?? 'Bilinmeyen hata')));
        }

        return view('admin.payments.iyzico-checkout', [
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
            return ['Müşteri', ''];
        }
$parts = preg_split('/\s+/', $fullName) ?: [];
        $first = (string) array_shift($parts);
        $last = trim(implode(' ', $parts));
        return [$first, $last !== '' ? $last : $first];
    }
}


