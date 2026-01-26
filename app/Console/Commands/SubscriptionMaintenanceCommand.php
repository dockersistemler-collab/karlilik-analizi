<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Models\Invoice;
use Carbon\Carbon;

class SubscriptionMaintenanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'subscriptions:maintain';

    /**
     * The console command description.
     */
    protected $description = 'Expire old subscriptions and reset monthly usage counters';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = Carbon::now();

        $reset = Subscription::where('status', 'active')
            ->whereNotNull('usage_reset_at')
            ->where('usage_reset_at', '<=', $now)
            ->update([
                'current_month_orders_count' => 0,
                'usage_reset_at' => $now->copy()->addMonth(),
            ]);

        $renewed = 0;
        $renewables = Subscription::where('status', 'active')
            ->where('auto_renew', true)
            ->where('ends_at', '<', $now)
            ->get();

        foreach ($renewables as $subscription) {
            $plan = $subscription->plan;
            if (!$plan) {
                continue;
            }

            $startsAt = $now->copy();
            $endsAt = $subscription->billing_period === 'yearly'
                ? $startsAt->copy()->addYear()
                : $startsAt->copy()->addMonth();

            $amount = $subscription->billing_period === 'yearly' && $plan->yearly_price
                ? $plan->yearly_price
                : $plan->price;

            $subscription->update([
                'status' => 'active',
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'amount' => $amount,
                'current_month_orders_count' => 0,
                'usage_reset_at' => $startsAt->copy()->addMonth(),
            ]);

            $invoiceNumber = 'INV-' . $now->format('Ymd') . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            Invoice::create([
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'invoice_number' => $invoiceNumber,
                'amount' => $amount,
                'currency' => 'TRY',
                'status' => 'paid',
                'issued_at' => $now,
                'paid_at' => $now,
                'billing_name' => $subscription->user?->billing_name ?: $subscription->user?->name,
                'billing_email' => $subscription->user?->billing_email ?: $subscription->user?->email,
                'billing_address' => $subscription->user?->billing_address,
            ]);

            $renewed++;
        }

        $expired = Subscription::where('status', 'active')
            ->where('ends_at', '<', $now)
            ->where('auto_renew', false)
            ->update([
                'status' => 'expired',
            ]);

        $this->info("Expired: {$expired} | Reset: {$reset} | Renewed: {$renewed}");

        return Command::SUCCESS;
    }
}
