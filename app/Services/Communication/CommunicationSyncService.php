<?php

namespace App\Services\Communication;

use App\Models\CommunicationMessage;
use App\Models\CommunicationSlaRule;
use App\Models\CommunicationThread;
use App\Models\MarketplaceStore;
use App\Services\Marketplaces\MarketplaceClientResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CommunicationSyncService
{
    public function __construct(
        private readonly MarketplaceClientResolver $resolver
    ) {
    }

    public function syncStore(int $marketplaceStoreId, array $channels = ['question', 'message', 'review']): void
    {
        $store = MarketplaceStore::query()
            ->with('marketplace')
            ->find($marketplaceStoreId);

        if (!$store || !$store->is_active) {
            return;
        }

        $client = $this->resolver->resolve($store);

        foreach ($channels as $channel) {
            $threads = $client->fetchThreads($store, $channel, now()->subDays(1));

            foreach ($threads as $payload) {
                DB::transaction(function () use ($payload, $store, $channel, $client): void {
                    $lastInboundAt = $this->toCarbon($payload['last_inbound_at'] ?? null);
                    $dueAt = $this->resolveDueAt(
                        $store->marketplace_id,
                        $channel,
                        $lastInboundAt,
                        $payload['due_at'] ?? null
                    );

                    $thread = CommunicationThread::query()->updateOrCreate(
                        [
                            'marketplace_store_id' => $store->id,
                            'channel' => $channel,
                            'external_thread_id' => (string) ($payload['external_thread_id'] ?? ''),
                        ],
                        [
                            'marketplace_id' => $store->marketplace_id,
                            'subject' => $payload['subject'] ?? null,
                            'product_sku' => $payload['product_sku'] ?? null,
                            'product_name' => $payload['product_name'] ?? null,
                            'customer_name' => $payload['customer_name'] ?? null,
                            'customer_external_id' => $payload['customer_external_id'] ?? null,
                            'status' => $this->normalizeStatus((string) ($payload['status'] ?? 'open')),
                            'last_inbound_at' => $lastInboundAt,
                            'due_at' => $dueAt,
                            'meta' => is_array($payload['meta'] ?? null) ? $payload['meta'] : null,
                        ]
                    );

                    $messages = $client->fetchThreadMessages($store, (string) $thread->external_thread_id);
                    foreach ($messages as $messagePayload) {
                        $this->upsertMessage($thread->id, $messagePayload);
                    }
                });
            }
        }
    }

    private function resolveDueAt(int $marketplaceId, string $channel, ?Carbon $lastInboundAt, mixed $fallbackDueAt): ?Carbon
    {
        if ($fallbackDueAt) {
            return $this->toCarbon($fallbackDueAt);
        }

        if (!$lastInboundAt) {
            return null;
        }

        $rule = CommunicationSlaRule::query()
            ->where('channel', $channel)
            ->where('is_active', true)
            ->where(function ($query) use ($marketplaceId): void {
                $query->where('marketplace_id', $marketplaceId)->orWhereNull('marketplace_id');
            })
            ->orderByRaw('marketplace_id is null')
            ->first();

        $minutes = (int) ($rule?->sla_minutes ?? 120);
        return $lastInboundAt->copy()->addMinutes($minutes);
    }

    private function upsertMessage(int $threadId, array $payload): void
    {
        $direction = in_array(($payload['direction'] ?? ''), ['inbound', 'outbound'], true)
            ? $payload['direction']
            : 'inbound';
        $body = (string) ($payload['body'] ?? '');
        $createdAtExternal = $this->toCarbon($payload['created_at_external'] ?? null);

        $existing = CommunicationMessage::query()
            ->where('thread_id', $threadId)
            ->where('direction', $direction)
            ->where('body', $body)
            ->when($createdAtExternal, fn ($q) => $q->where('created_at_external', $createdAtExternal))
            ->first();

        if ($existing) {
            return;
        }

        CommunicationMessage::query()->create([
            'thread_id' => $threadId,
            'direction' => $direction,
            'body' => $body,
            'created_at_external' => $createdAtExternal,
            'sender_type' => in_array(($payload['sender_type'] ?? ''), ['customer', 'seller', 'system'], true)
                ? $payload['sender_type']
                : 'customer',
            'meta' => is_array($payload['meta'] ?? null) ? $payload['meta'] : null,
        ]);
    }

    private function toCarbon(mixed $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeStatus(string $status): string
    {
        return in_array($status, ['open', 'pending', 'answered', 'closed', 'overdue'], true)
            ? $status
            : 'open';
    }
}

