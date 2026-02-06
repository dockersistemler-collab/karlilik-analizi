<?php

namespace Tests\Feature\Mail;

use App\Events\PaymentSucceeded;
use App\Mail\TemplateMailable;
use App\Models\MailLog;
use App\Models\MailTemplate;
use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\User;
use App\Services\Payments\IyzicoClient;
use App\Services\Purchases\ModulePurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaymentSucceededMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_iyzico_success_dispatches_mail_and_logs(): void
    {
        Mail::fake();

        MailTemplate::create([
            'key' => 'payment.succeeded',
            'channel' => 'email',
            'category' => 'billing',
            'subject' => 'Ödeme alındı — Teşekkürler',
            'body_html' => '<p>Merhaba {{user_name}}</p>',
            'enabled' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $module = Module::create([
            'code' => 'feature.sample',
            'name' => 'Sample',
            'description' => 'Sample module',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $purchase = ModulePurchase::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'provider' => 'iyzico',
            'provider_payment_id' => null,
            'amount' => 100,
            'currency' => 'TRY',
            'period' => 'monthly',
            'status' => 'pending',
        ]);

        $this->mock(IyzicoClient::class, function ($mock) use ($purchase) {
            $mock->shouldReceive('retrieveCheckoutForm')
                ->once()
                ->andReturn([
                    'status' => 'SUCCESS',
                    'conversationId' => 'purchase:'.$purchase->id,
                    'paymentId' => 'tx_123',
                ]);
        });

        $this->mock(ModulePurchaseService::class, function ($mock) use ($purchase) {
            $mock->shouldReceive('markPaid')
                ->once()
                ->andReturn($purchase);
        });

        $this->post(route('iyzico.callback'), ['token' => 'tok'])
            ->assertRedirect();

        Mail::assertQueued(TemplateMailable::class);

        $log = MailLog::query()
            ->where('key', 'payment.succeeded')
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('tx_123', $log->metadata_json['transaction_id'] ?? null);
    }

    public function test_payment_succeeded_dedupes_by_transaction_id(): void
    {
        Mail::fake();

        MailTemplate::create([
            'key' => 'payment.succeeded',
            'channel' => 'email',
            'category' => 'billing',
            'subject' => 'Ödeme alındı — Teşekkürler',
            'body_html' => '<p>Merhaba {{user_name}}</p>',
            'enabled' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        event(new PaymentSucceeded(
            $user->id,
            null,
            null,
            '100',
            'TRY',
            'iyzico',
            'tx_456',
            now()->toDateTimeString()
        ));

        event(new PaymentSucceeded(
            $user->id,
            null,
            null,
            '100',
            'TRY',
            'iyzico',
            'tx_456',
            now()->toDateTimeString()
        ));

        Mail::assertQueued(TemplateMailable::class, 1);

        $successCount = MailLog::query()
            ->where('key', 'payment.succeeded')
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->count();

        $dedupedCount = MailLog::query()
            ->where('key', 'payment.succeeded')
            ->where('user_id', $user->id)
            ->where('status', 'deduped')
            ->count();

        $this->assertSame(1, $successCount);
        $this->assertSame(1, $dedupedCount);
    }
}
