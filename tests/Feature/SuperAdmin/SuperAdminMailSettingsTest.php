<?php

namespace Tests\Feature\SuperAdmin;

use App\Mail\SystemSettingsTestMail;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Services\SystemSettings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SuperAdminMailSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_mail_settings_tab(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.settings.index', ['tab' => 'mail']))
            ->assertOk()
            ->assertSee('Mail & Bildirim Ayarları', false);
    }

    public function test_normal_admin_cannot_access_settings(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('super-admin.settings.index'))
            ->assertForbidden();
    }

    public function test_mail_settings_are_saved_and_password_is_preserved_when_empty(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $settings = app(SettingsRepository::class);
        $settings->set('mail', 'smtp.password', 'old-secret', true, $superAdmin->id);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.settings.mail.update'), [
                'override_enabled' => '1',
                'from_name' => 'Test From',
                'from_address' => 'from@example.com',
                'smtp_host' => 'smtp.example.com',
                'smtp_port' => 2525,
                'smtp_username' => 'smtp-user',
                'smtp_password' => '',
                'smtp_encryption' => 'tls',
                'default_quiet_hours_start' => '22:00',
                'default_quiet_hours_end' => '08:00',
                'critical_email_default_enabled' => '1',
            ])
            ->assertRedirect(route('super-admin.settings.index', ['tab' => 'mail']));

        $this->assertSame('old-secret', $settings->get('mail', 'smtp.password'));
        $this->assertSame('smtp.example.com', $settings->get('mail', 'smtp.host'));
        $this->assertSame('2525', $settings->get('mail', 'smtp.port'));
    }

    public function test_override_enabled_applies_config(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $settings = app(SettingsRepository::class);
        $settings->set('mail', 'override_enabled', true, false, $superAdmin->id);
        $settings->set('mail', 'smtp.host', 'smtp.override.local', false, $superAdmin->id);

        $provider = new AppServiceProvider($this->app);
        $provider->boot();

        $this->assertSame('smtp.override.local', config('mail.mailers.smtp.host'));
    }

    public function test_test_mail_endpoint_sends_mail(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.settings.mail.test'), [
                'to_email' => 'test@example.com',
            ])
            ->assertRedirect(route('super-admin.settings.index', ['tab' => 'mail']));

        Mail::assertSent(SystemSettingsTestMail::class, function (SystemSettingsTestMail $mail) {
            return $mail->hasTo('test@example.com');
        });
    }
}
