<?php

namespace Tests\Feature\Mail;

use App\Events\PaymentFailed;
use App\Mail\TemplateMailable;
use App\Models\MailLog;
use App\Models\MailTemplate;
use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\User;
use App\Services\Payments\IyzicoClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaymentFailedMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_iyzico_failure_dispatches_mail_and_logs(): void
    {
        Mail::fake();

        MailTemplate::create([
            'key' => 'payment.failed',
            'channel' => 'email',
            'category' => 'billing',
            'subject' => 'Odeme basarisiz',
            'body_html' => '<p>Merhaba {{user_name}}</p>',
            'enabled' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $module = Module::create([
            'code' => 'feature.test',
            'name' => 'Test Module',
            'description' => null,
            'type' => 'feature',
            'billing_type' => 'one_time',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $purchase = ModulePurchase::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'provider' => 'iyzico',
            'provider_payment_id' => null,
            'amount' => 99.90,
            'currency' => 'TRY',
            'period' => 'one_time',
            'status' => 'pending',
            'starts_at' => null,
            'ends_at' => null,
            'meta' => [],
        ]);

        $this->mock(IyzicoClient::class, function ($mock) use ($purchase): void {
            $mock->shouldReceive('retrieveCheckoutForm')
                ->once()
                ->andReturn([
                    'status' => 'FAILURE',
                    'conversationId' => 'purchase:'.$purchase->id,
                    'paymentId' => null,
                    'paymentConversationId' => null,
                    'errorCode' => 'ERR-1',
                    'errorMessage' => 'Denied',
                    'raw' => [],
                ]);
        });

        $response = $this->post(route('iyzico.callback'), ['token' => 'tok_123']);
        $response->assertRedirect(route('portal.addons.index'));

        Mail::assertQueued(TemplateMailable::class);

        $log = MailLog::query()
            ->where('key', 'payment.failed')
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('ERR-1', $log->metadata_json['error_code'] ?? null);
    }

    public function test_payment_failed_dedupes_by_error_code(): void
    {
        Mail::fake();

        MailTemplate::create([
            'key' => 'payment.failed',
            'channel' => 'email',
            'category' => 'billing',
            'subject' => 'Odeme basarisiz',
            'body_html' => '<p>Merhaba {{user_name}}</p>',
            'enabled' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        event(new PaymentFailed(
            $user->id,
            null,
            null,
            '99.90',
            'TRY',
            'iyzico',
            'ERR-DEDUP',
            'Denied',
            now()->toDateTimeString()
        ));

        event(new PaymentFailed(
            $user->id,
            null,
            null,
            '99.90',
            'TRY',
            'iyzico',
            'ERR-DEDUP',
            'Denied',
            now()->toDateTimeString()
        ));

        Mail::assertQueued(TemplateMailable::class, 1);

        $successCount = MailLog::query()
            ->where('key', 'payment.failed')
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->count();

        $dedupedCount = MailLog::query()
            ->where('key', 'payment.failed')
            ->where('user_id', $user->id)
            ->where('status', 'deduped')
            ->count();

        $this->assertSame(1, $successCount);
        $this->assertSame(1, $dedupedCount);
    }
}

