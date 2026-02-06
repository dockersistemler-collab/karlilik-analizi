<?php

namespace App\Console\Commands;

use App\Enums\NotificationSource;
use App\Enums\NotificationType;
use App\Models\BillingSubscription;
use App\Models\User;
use App\Services\BillingEventLogger;
use App\Services\Notifications\NotificationService;
use App\Services\SystemSettings\SettingsRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class BillingDunningRun extends Command
{
    protected $signature = 'billing:dunning-run {--tenant=}';

    protected $description = 'Process billing dunning grace period and reminders.';

    public function handle(SettingsRepository $settings, NotificationService $notifications, BillingEventLogger $events): int
    {
        $tenantId = $this->option('tenant');
        $now = Carbon::now();

        $graceDays = (int) $settings->get('billing', 'dunning.grace_days', 3);
        $sendReminders = filter_var($settings->get('billing', 'dunning.send_reminders', true), FILTER_VALIDATE_BOOLEAN);
        $reminderDay1 = (int) $settings->get('billing', 'dunning.reminder_day_1', 0);
        $reminderDay2 = (int) $settings->get('billing', 'dunning.reminder_day_2', 2);
        $autoDowngrade = filter_var($settings->get('billing', 'dunning.auto_downgrade', true), FILTER_VALIDATE_BOOLEAN);

        $query = BillingSubscription::query()
            ->whereNotNull('past_due_since')
            ->whereNotNull('grace_until')
            ->whereIn('status', ['UNPAID', 'PAST_DUE', 'FAILURE', 'FAILED']);

        if ($tenantId) {
            $query->where('tenant_id', (int) $tenantId);
        }
$query->orderBy('tenant_id')
            ->chunk(200, function ($subscriptions) use (
                $now,
                $graceDays,
                $sendReminders,
                $reminderDay1,
                $reminderDay2,
                $autoDowngrade,
                $notifications,
                $events
            ) {
                foreach ($subscriptions as $subscription) {
                    $tenant = User::query()->find($subscription->tenant_id);
                    if (!$tenant) {
                        continue;
                    }

                    if (!$subscription->grace_until) {
                        $subscription->grace_until = $subscription->past_due_since?->copy()
                            ->addDays(max(0, $graceDays));
                        $subscription->save();
                    }

                    if ($autoDowngrade && $subscription->grace_until && $subscription->grace_until->lessThanOrEqualTo($now)) {
                        if ($tenant->plan_code !== 'free') {
                            $tenant->plan_code = 'free';
                            $tenant->save();
                        }

                        if ($subscription->status !== 'SUSPENDED') {
                            $subscription->status = 'SUSPENDED';
                            $subscription->save();
                        }
$events->record([
                            'tenant_id' => $tenant->id,
                            'user_id' => $tenant->id,
                            'subscription_id' => $subscription->id,
                            'type' => 'dunning.retry_failed',
                            'status' => $subscription->status,
                            'provider' => $subscription->provider,
                            'payload' => [
                                'grace_until' => $subscription->grace_until?->toIso8601String(),
                            ],
                        ]);

                        $notifications->notifyUser($tenant, [
                            'tenant_id' => $tenant->id,
                            'user_id' => $tenant->id,
                            'source' => NotificationSource::System->value,
                            'type' => NotificationType::Critical->value,
                            'title' => 'Plan dusuruldu',
                            'body' => 'Odeme alinamadigi icin plan ucretsiz pakete dusuruldu.',
                            'dedupe_key' => "subscription:{$tenant->id}:downgraded",
                            'group_key' => "subscription:{$tenant->id}",
                            'dedupe_window_minutes' => 1440,
                            'data' => [
                                'subscription_id' => $subscription->id,
                                'status' => $subscription->status,
                            ],
                        ]);
                        continue;
                    }

                    if (!$sendReminders || !$subscription->past_due_since) {
                        continue;
                    }
$thresholds = array_values(array_unique(array_filter([
                        $reminderDay1,
                        $reminderDay2,
                    ], static fn ($value) => is_int($value) && $value >= 0)));

                    foreach ($thresholds as $threshold) {
                        $targetAt = $subscription->past_due_since->copy()->addDays($threshold);
                        if ($now->lessThan($targetAt)) {
                            continue;
                        }
$lastSent = $subscription->last_dunning_sent_at;
                        if ($lastSent && $lastSent->greaterThanOrEqualTo($targetAt)) {
                            continue;
                        }
$events->record([
                            'tenant_id' => $tenant->id,
                            'user_id' => $tenant->id,
                            'subscription_id' => $subscription->id,
                            'type' => 'dunning.retry_attempt',
                            'status' => $subscription->status,
                            'provider' => $subscription->provider,
                            'payload' => [
                                'reminder_day' => $threshold,
                                'past_due_since' => $subscription->past_due_since?->toIso8601String(),
                            ],
                        ]);

                        $notifications->notifyUser($tenant, [
                            'tenant_id' => $tenant->id,
                            'user_id' => $tenant->id,
                            'source' => NotificationSource::System->value,
                            'type' => NotificationType::Operational->value,
                            'title' => 'Odeme alinmadi',
                            'body' => 'Odeme alinamadi, grace suresi devam ediyor.',
                            'dedupe_key' => "subscription:{$tenant->id}:past_due:reminder:{$threshold}",
                            'group_key' => "subscription:{$tenant->id}",
                            'dedupe_window_minutes' => 1440,
                            'data' => [
                                'subscription_id' => $subscription->id,
                                'status' => $subscription->status,
                                'past_due_since' => $subscription->past_due_since?->toIso8601String(), 'grace_until' => $subscription->grace_until?->toIso8601String(), 'reminder_day' => $threshold,
                            ],
                        ]);

                        $events->record(['tenant_id' => $tenant->id,
                            'user_id' => $tenant->id,
                            'subscription_id' => $subscription->id,
                            'type' => 'dunning.retry_succeeded',
                            'status' => $subscription->status,
                            'provider' => $subscription->provider,
                            'payload' => [
                                'reminder_day' => $threshold,
                            ],
                        ]);

                        $subscription->last_dunning_sent_at = $now;
                        $subscription->save();
                        break;
                    }
                }
            });

        $this->info('Billing dunning run completed.');

        return self::SUCCESS;
    }
}
