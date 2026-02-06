<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\MailLog;
use App\Models\MailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailLogIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_filters_with_query_params(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        MailTemplate::create([
            'key' => 'quota.warning_80',
            'channel' => 'email',
            'category' => 'billing',
            'subject' => 'Test',
            'body_html' => '<p>Test</p>',
            'enabled' => true,
        ]);

        MailLog::create([
            'key' => 'quota.warning_80',
            'user_id' => $superAdmin->id,
            'status' => 'success',
            'provider_message_id' => null,
            'error' => null,
            'metadata_json' => [],
            'sent_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.mail-logs.index', [
                'key' => 'quota.warning_80',
                'category' => 'billing',
                'status' => 'success',
                'email' => $superAdmin->email,
                'date_from' => now()->subDay()->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk();
    }

    public function test_export_returns_csv(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        MailTemplate::create([
            'key' => 'payment.succeeded',
            'channel' => 'email',
            'category' => 'billing',
            'subject' => 'Test',
            'body_html' => '<p>Test</p>',
            'enabled' => true,
        ]);

        MailLog::create([
            'key' => 'payment.succeeded',
            'user_id' => $superAdmin->id,
            'status' => 'success',
            'provider_message_id' => null,
            'error' => null,
            'metadata_json' => [],
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($superAdmin)
            ->get(route('super-admin.mail-logs.export', [
                'key' => 'payment.succeeded',
            ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('created_at,key,category,status', $response->streamedContent());
    }
}
