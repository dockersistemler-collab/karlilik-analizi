<?php

namespace Tests\Feature\Mail;

use App\Events\InvoiceCreated;
use App\Mail\TemplateMailable;
use App\Models\MailLog;
use App\Models\MailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoiceCreatedMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_created_dispatches_mail_and_logs(): void
    {
        Mail::fake();

        MailTemplate::create([
            'key' => 'invoice.created',
            'channel' => 'email',
            'category' => 'billing',
            'subject' => 'Faturaniz hazir',
            'body_html' => '<p>Merhaba {{user_name}}</p>',
            'enabled' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        event(new InvoiceCreated(
            $user->id,
            123,
            'trendyol',
            'inv-1',
            'INV-0001',
            'https://example.test/invoices/inv-1',
            '199.90',
            'TRY',
            now()->toDateTimeString()
        ));

        Mail::assertQueued(TemplateMailable::class);

        $log = MailLog::query()
            ->where('key', 'invoice.created')
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('inv-1', $log->metadata_json['invoice_id'] ?? null);
    }

    public function test_invoice_created_dedupes_by_invoice_id(): void
    {
        Mail::fake();

        MailTemplate::create([
            'key' => 'invoice.created',
            'channel' => 'email',
            'category' => 'billing',
            'subject' => 'Faturaniz hazir',
            'body_html' => '<p>Merhaba {{user_name}}</p>',
            'enabled' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        event(new InvoiceCreated(
            $user->id,
            null,
            null,
            'inv-2',
            'INV-0002',
            null,
            '99.90',
            'TRY',
            now()->toDateTimeString()
        ));

        event(new InvoiceCreated(
            $user->id,
            null,
            null,
            'inv-2',
            'INV-0002',
            null,
            '99.90',
            'TRY',
            now()->toDateTimeString()
        ));

        Mail::assertQueued(TemplateMailable::class, 1);

        $successCount = MailLog::query()
            ->where('key', 'invoice.created')
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->count();

        $dedupedCount = MailLog::query()
            ->where('key', 'invoice.created')
            ->where('user_id', $user->id)
            ->where('status', 'deduped')
            ->count();

        $this->assertSame(1, $successCount);
        $this->assertSame(1, $dedupedCount);
    }
}
