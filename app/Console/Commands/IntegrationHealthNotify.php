<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\IntegrationHealthNotifier;
use Illuminate\Console\Command;

class IntegrationHealthNotify extends Command
{
    protected $signature = 'integrations:health-notify {--tenant=}';

    protected $description = 'Notify tenants about integration health status changes.';

    public function handle(IntegrationHealthNotifier $notifier): int
    {
        $tenantId = $this->option('tenant');
        if ($tenantId) {
            $notifier->notifyTenant((int) $tenantId);
            $this->info('Integration health notified for tenant '.$tenantId);
            return self::SUCCESS;
        }

        User::query()
            ->where('role', 'client')
            ->where('is_active', true)
            ->orderBy('id')
            ->chunk(200, function ($users) use ($notifier) {
                foreach ($users as $user) {
                    $notifier->notifyTenant((int) $user->id);
                }
            });

        $this->info('Integration health notifications completed.');

        return self::SUCCESS;
    }
}
