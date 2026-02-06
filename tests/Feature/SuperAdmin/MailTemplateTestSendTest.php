<?php

namespace Tests\Feature\SuperAdmin;

use App\Mail\TemplateMailable;
use App\Models\MailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailTemplateTestSendTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_page_is_accessible_for_super_admin(): void
    {
        $template = MailTemplate::create([
            'key' => 'quota.warning_80',
            'channel' => 'email',
            'category' => 'billing',
            'subject' => 'Test {{user_name}}',
            'body_html' => '<p>Merhaba {{user_name}}</p>',
            'enabled' => true,
        ]);

        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.notifications.mail-templates.show', $template))
            ->assertOk()
            ->assertSee('Kullanılan Değişkenler')
            ->assertSee('user_name');
    }

    public function test_test_send_creates_log_and_rate_limits(): void
    {
        Mail::fake();

        $template = MailTemplate::create([
            'key' => 'quota.warning_80',
            'channel' => 'email',
            'category' => 'billing',
            'subject' => 'Test {{user_name}}',
            'body_html' => '<p>Merhaba {{user_name}}</p>',
            'enabled' => true,
        ]);

        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.notifications.mail-templates.test', $template))
            ->assertRedirect();

        Mail::assertQueued(TemplateMailable::class, 1);

        $this->assertDatabaseHas('mail_logs', [
            'key' => 'quota.warning_80',
            'user_id' => $superAdmin->id,
            'status' => 'success',
        ]);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.notifications.mail-templates.test', $template))
            ->assertRedirect();

        Mail::assertQueued(TemplateMailable::class, 1);

        $this->assertDatabaseHas('mail_logs', [
            'key' => 'quota.warning_80',
            'user_id' => $superAdmin->id,
            'status' => 'deduped',
        ]);
    }
}
