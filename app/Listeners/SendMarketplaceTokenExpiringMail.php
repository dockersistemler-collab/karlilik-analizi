<?php

namespace App\Listeners;

use App\Events\MarketplaceTokenExpiring;
use App\Models\MailLog;
use App\Models\MarketplaceCredential;
use App\Models\User;
use App\Services\Mail\MailSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

class SendMarketplaceTokenExpiringMail implements ShouldQueue
{
    public function __construct(private readonly MailSender $sender)
    {
    }

    public function handle(MarketplaceTokenExpiring $event): void
    {
        $user = User::query()->find($event->userId);
        if (!$user) {
            return;
        }

        if ($this->isDeduped($event, $user->id)) {
            if (!$this->hasDedupedLog($event, $user->id)) {
                MailLog::create([
                    'key' => 'mp.token_expiring',
                    'user_id' => $user->id,
                    'status' => 'deduped',
                    'provider_message_id' => null,
                    'error' => null,
                    'metadata_json' => [
                        'marketplace_credential_id' => $event->marketplaceCredentialId,
                        'days_left' => $event->daysLeft,
                        'expires_at' => $event->expiresAt,
                        'marketplace' => $event->marketplace,
                        'occurred_at' => $event->occurredAt,
                        'reason' => 'dedupe_24h',
                    ],
                    'sent_at' => null,
                ]);
            }
            return;
        }
$credential = MarketplaceCredential::query()
            ->with('marketplace')
            ->find($event->marketplaceCredentialId);

        $marketplaceName = $credential?->marketplace?->name ?? $event->marketplace;
        $marketplaceId = $credential?->marketplace_id;
$reconnectUrl = null;
        if ($marketplaceId && Route::has('portal.integrations.edit')) {
            $reconnectUrl = route('portal.integrations.edit', $marketplaceId);
        }
$dashboardUrl = Route::has('portal.dashboard') ? route('portal.dashboard') : null;
        $supportUrl = Route::has('portal.help.support') ? route('portal.help.support') : null;

        $this->sender->send('mp.token_expiring', $user, [
            'user_name' => $user->name,
            'marketplace' => $marketplaceName,
            'expires_at' => $event->expiresAt,
            'days_left' => (string) $event->daysLeft,
            'reconnect_url' => $reconnectUrl,
            'dashboard_url' => $dashboardUrl,
            'support_url' => $supportUrl,
        ], [
            'marketplace_credential_id' => $event->marketplaceCredentialId,
            'days_left' => $event->daysLeft,
            'expires_at' => $event->expiresAt,
            'marketplace' => $event->marketplace,
            'occurred_at' => $event->occurredAt,
        ]);
    }

    private function isDeduped(MarketplaceTokenExpiring $event, int $userId): bool
    {
        $since = Carbon::now()->subDay();

        return MailLog::query()
            ->where('key', 'mp.token_expiring')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->where('metadata_json->marketplace_credential_id', $event->marketplaceCredentialId)
            ->where('metadata_json->days_left', $event->daysLeft)
            ->where('status', 'success')
            ->exists();
    }

    private function hasDedupedLog(MarketplaceTokenExpiring $event, int $userId): bool
    {
        $since = Carbon::now()->subDay();

        return MailLog::query()
            ->where('key', 'mp.token_expiring')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->where('metadata_json->marketplace_credential_id', $event->marketplaceCredentialId)
            ->where('metadata_json->days_left', $event->daysLeft)
            ->where('status', 'deduped')
            ->exists();
    }
}




