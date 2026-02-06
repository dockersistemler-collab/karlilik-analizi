<?php

namespace App\Console\Commands;

use App\Events\MarketplaceTokenExpiring;
use App\Models\MarketplaceCredential;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;

class CheckMarketplaceTokenExpirationsCommand extends Command
{
    protected $signature = 'marketplace:check-token-expirations';

    protected $description = 'Notify users when marketplace tokens are nearing expiration (7/3/1 days).';

    public function handle(): int
    {
        $now = Carbon::now();
        $thresholds = [7, 3, 1];

        foreach ($thresholds as $daysLeft) {
            $targetDate = $now->copy()->addDays($daysLeft)->toDateString();

            $credentials = MarketplaceCredential::query()
                ->whereNotNull('token_expires_at')
                ->whereDate('token_expires_at', $targetDate)
                ->with('marketplace')
                ->get();

            foreach ($credentials as $credential) {
                $expiresAt = $credential->token_expires_at;
                if (!$expiresAt || $expiresAt->lessThanOrEqualTo($now)) {
                    continue;
                }
$marketplaceLabel = $credential->marketplace?->name ?? $credential->marketplace?->code ?? 'pazaryeri';

                event(new MarketplaceTokenExpiring(
                    $credential->user_id,
                    $credential->id,
                    (string) $marketplaceLabel,
                    $expiresAt->toDateTimeString(),
                    $daysLeft,
                    $now->toDateTimeString()
                ));
            }
        }

        return Command::SUCCESS;
    }
}
