<?php

namespace Tests\Feature\Notifications;

use App\Console\Commands\SendModuleRenewalReminders;

use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\NotificationLog;
use App\Models\User;
use App\Notifications\RenewalReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ModuleRenewalRemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_reminder_type_is_not_sent_twice(): void
    {
        Notification::fake();

        $now = Carbon::create(2026, 1, 1, 9, 0, 0, 'Europe/Istanbul');
        Carbon::setTestNow($now);

        $user = User::factory()->create(['role' => 'client']);

        $module = Module::create([
            'code' => 'feature.exports',
            'name' => 'Exportlar',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $purchase = ModulePurchase::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'provider' => 'manual',
            'provider_payment_id' => null,
            'amount' => 99.90,
            'currency' => 'TRY',
            'period' => 'monthly',
            'status' => 'paid',
            'starts_at' => $now->copy()->timezone('UTC'),
            'ends_at' => $now->copy()->addDays(3)->addHours(2)->timezone('UTC'),
            'meta' => ['source' => 'test'],
        ]);

        app(SendModuleRenewalReminders::class)->handle();
        app(SendModuleRenewalReminders::class)->handle();

        Notification::assertSentTo($user, RenewalReminderNotification::class, function (RenewalReminderNotification $n) use ($purchase) {
            return $n->purchase->id === $purchase->id && $n->daysLeft === 3;
        });

        $this->assertSame(1, NotificationLog::query()->where('module_purchase_id', $purchase->id)->where('type', 'renewal.d3')->count());
    }

    public function test_d3_and_d1_reminders_trigger_on_correct_days(): void
    {
        Notification::fake();

        $base = Carbon::create(2026, 1, 10, 9, 0, 0, 'Europe/Istanbul');
        Carbon::setTestNow($base);

        $user = User::factory()->create(['role' => 'client']);

        $module = Module::create([
            'code' => 'feature.reports',
            'name' => 'Raporlar',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $endsAtIstanbul = $base->copy()->addDays(3)->addHours(1);

        $purchase = ModulePurchase::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'provider' => 'manual',
            'provider_payment_id' => null,
            'amount' => 199.90,
            'currency' => 'TRY',
            'period' => 'monthly',
            'status' => 'paid',
            'starts_at' => $base->copy()->timezone('UTC'),
            'ends_at' => $endsAtIstanbul->copy()->timezone('UTC'),
            'meta' => ['source' => 'test'],
        ]);

        // D3
        app(SendModuleRenewalReminders::class)->handle();

        // D1 (2 days later)
        Carbon::setTestNow($base->copy()->addDays(2));
        app(SendModuleRenewalReminders::class)->handle();

        Notification::assertSentTo($user, RenewalReminderNotification::class, function (RenewalReminderNotification $n) use ($purchase) {
            return $n->purchase->id === $purchase->id && $n->daysLeft === 3;
        });
        Notification::assertSentTo($user, RenewalReminderNotification::class, function (RenewalReminderNotification $n) use ($purchase) {
            return $n->purchase->id === $purchase->id && $n->daysLeft === 1;
        });

        $this->assertSame(1, NotificationLog::query()->where('module_purchase_id', $purchase->id)->where('type', 'renewal.d3')->count());
        $this->assertSame(1, NotificationLog::query()->where('module_purchase_id', $purchase->id)->where('type', 'renewal.d1')->count());
    }

    public function test_renewal_email_contains_cta_link_to_my_modules(): void
    {
        $user = User::factory()->create(['role' => 'client', 'email' => 'test@example.com']);

        $module = Module::create([
            'code' => 'feature.reports',
            'name' => 'Raporlar',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $purchase = ModulePurchase::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'provider' => 'manual',
            'provider_payment_id' => null,
            'amount' => 99.90,
            'currency' => 'TRY',
            'period' => 'monthly',
            'status' => 'paid',
            'starts_at' => Carbon::now()->subDays(20),
            'ends_at' => Carbon::now()->addDays(3),
            'meta' => ['source' => 'test'],
        ]);

        Mail::fake();

        $notification = new RenewalReminderNotification($purchase, 3);
        $mailable = $notification->toMail($user);
        $array = $mailable->toArray();

        $this->assertStringContainsString(route('portal.modules.mine'), (string) ($array['actionUrl'] ?? ''));
    }
}

