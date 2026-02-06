<?php

namespace Tests\Feature\Mail;

use App\Events\InvoiceFailed;
use App\Mail\TemplateMailable;
use App\Listeners\SendInvoiceFailedMail;
use App\Models\EInvoice;
use App\Models\MailLog;
use App\Models\MailTemplate;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\EInvoices\EInvoiceNumberingService;
use App\Services\EInvoices\EInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoiceFailedMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_failure_dispatches_mail_and_logs(): void
    {
        Mail::fake();

        MailTemplate::create([
            'key' => 'invoice.failed',
            'channel' => 'email',
            'category' => 'billing',
            'subject' => 'Fatura olusturulamadi',
            'body_html' => '<p>Merhaba {{user_name}}</p>',
            'enabled' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $plan = Plan::create([
            'name' => 'Basic',
            'slug' => 'basic',
            'description' => null,
            'price' => 100,
            'yearly_price' => 1000,
            'billing_period' => 'monthly',
            'max_products' => 0,
            'max_marketplaces' => 0,
            'max_orders_per_month' => 0,
            'max_tickets_per_month' => 0,
            'api_access' => false,
            'advanced_reports' => false,
            'priority_support' => false,
            'custom_integrations' => false,
            'features' => [],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'cancelled_at' => null,
            'amount' => 100,
            'billing_period' => 'monthly',
            'auto_renew' => true,
            'current_products_count' => 0,
            'current_marketplaces_count' => 0,
            'current_month_orders_count' => 0,
            'usage_reset_at' => null,
        ]);

        $invoice = EInvoice::create([
            'user_id' => $user->id,
            'source_type' => 'order',
            'source_id' => 123,
            'marketplace' => 'trendyol',
            'status' => 'draft',
            'type' => 'sale',
            'currency' => 'TRY',
            'subtotal' => 100,
            'tax_total' => 18,
            'discount_total' => 0,
            'grand_total' => 118,
        ]);

        $this->app->instance(EInvoiceNumberingService::class, new class {
            public function nextNumber(): string
            {
                throw new \RuntimeException('Numbering failed');
            }
        });
        $this->app->forgetInstance(EInvoiceService::class);

        Event::fake();

        $threw = false;
        try {
            app(EInvoiceService::class)->issue($invoice);
        } catch (\Throwable $e) {
            $threw = true;
        }

        $this->assertTrue($threw);

        app(SendInvoiceFailedMail::class)->handle(new InvoiceFailed(
            $user->id,
            123,
            'trendyol',
            (string) $invoice->id,
            'ERR-INV',
            'Numbering failed',
            now()->toDateTimeString()
        ));

        Mail::assertQueued(TemplateMailable::class);

        $log = MailLog::query()
            ->where('key', 'invoice.failed')
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(123, $log->metadata_json['order_id'] ?? null);
    }

    public function test_invoice_failed_dedupes_by_order_id(): void
    {
        Mail::fake();

        MailTemplate::create([
            'key' => 'invoice.failed',
            'channel' => 'email',
            'category' => 'billing',
            'subject' => 'Fatura olusturulamadi',
            'body_html' => '<p>Merhaba {{user_name}}</p>',
            'enabled' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        event(new InvoiceFailed(
            $user->id,
            456,
            'trendyol',
            null,
            'ERR-INV',
            'Failed',
            now()->toDateTimeString()
        ));

        event(new InvoiceFailed(
            $user->id,
            456,
            'trendyol',
            null,
            'ERR-INV',
            'Failed',
            now()->toDateTimeString()
        ));

        Mail::assertQueued(TemplateMailable::class, 1);

        $successCount = MailLog::query()
            ->where('key', 'invoice.failed')
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->count();

        $dedupedCount = MailLog::query()
            ->where('key', 'invoice.failed')
            ->where('user_id', $user->id)
            ->where('status', 'deduped')
            ->count();

        $this->assertSame(1, $successCount);
        $this->assertSame(1, $dedupedCount);
    }
}
