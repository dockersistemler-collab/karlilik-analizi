<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncCommunicationJob;
use App\Models\CommunicationMessage;
use App\Models\CommunicationSetting;
use App\Models\CommunicationTemplate;
use App\Models\CommunicationThread;
use App\Models\Marketplace;
use App\Models\MarketplaceStore;
use App\Services\Communication\CommunicationAiSuggestService;
use App\Services\Marketplaces\MarketplaceClientResolver;
use App\Support\SupportUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CommunicationCenterController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('portal.communication-center.questions');
    }

    public function list(Request $request, string $channel): View
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 401);
        $this->authorize('viewAny', CommunicationThread::class);
        $allowedPerPage = [10, 25, 50, 100];
        $perPage = (int) $request->input('per_page', 25);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 25;
        }

        $marketplaceId = (int) $request->integer('marketplace_id');
        $storeId = (int) $request->integer('store_id');
        $status = (string) $request->input('status', '');
        $dateFrom = (string) $request->input('date_from', '');
        $dateTo = (string) $request->input('date_to', '');
        $search = trim((string) $request->input('search', ''));
        $globalSettings = CommunicationSetting::query()->whereNull('user_id')->first();
        $weights = (array) ($globalSettings?->priority_weights ?? []);
        $criticalThresholdMinutes = max(1, (int) ($weights['critical_minutes'] ?? 30));

        // Self-heal stale statuses: if we already replied after the latest inbound,
        // status must be "answered" (not pending/open/overdue).
        CommunicationThread::query()
            ->when(!$user->isSuperAdmin(), fn (Builder $q) => $q->forUser($user))
            ->whereIn('status', ['open', 'pending', 'overdue'])
            ->whereNotNull('last_inbound_at')
            ->whereNotNull('last_outbound_at')
            ->whereColumn('last_outbound_at', '>=', 'last_inbound_at')
            ->update(['status' => 'answered']);

        $threadsQuery = CommunicationThread::query()
            ->with(['marketplace', 'marketplaceStore', 'messages' => function ($query): void {
                $query->orderBy('id');
            }])
            ->when(!$user->isSuperAdmin(), fn (Builder $q) => $q->forUser($user));

        $this->applyFilters(
            $threadsQuery,
            $channel,
            $marketplaceId,
            $storeId,
            $status,
            $dateFrom,
            $dateTo,
            $search,
            false
        );

        $threads = $threadsQuery
            ->orderBy('due_at')
            ->orderByDesc('priority_score')
            ->paginate($perPage)
            ->withQueryString();

        $channelCounts = CommunicationThread::query()
            ->when(!$user->isSuperAdmin(), fn (Builder $q) => $q->forUser($user))
            ->whereIn('status', ['open', 'pending', 'overdue'])
            ->selectRaw('channel, COUNT(*) as aggregate')
            ->groupBy('channel')
            ->pluck('aggregate', 'channel');

        $marketplaceCountsQuery = CommunicationThread::query()
            ->when(!$user->isSuperAdmin(), fn (Builder $q) => $q->forUser($user))
            ->whereIn('status', ['open', 'pending', 'overdue']);

        $this->applyFilters(
            $marketplaceCountsQuery,
            $channel,
            $marketplaceId,
            $storeId,
            $status,
            $dateFrom,
            $dateTo,
            $search,
            true
        );

        $marketplaceCounts = $marketplaceCountsQuery
            ->selectRaw('marketplace_id, COUNT(*) as aggregate')
            ->groupBy('marketplace_id')
            ->pluck('aggregate', 'marketplace_id');

        $statusCountsQuery = CommunicationThread::query()
            ->when(!$user->isSuperAdmin(), fn (Builder $q) => $q->forUser($user));
        $this->applyFilters(
            $statusCountsQuery,
            $channel,
            $marketplaceId,
            $storeId,
            '',
            $dateFrom,
            $dateTo,
            $search,
            false
        );
        $statusCounts = $statusCountsQuery
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $storeCountsQuery = CommunicationThread::query()
            ->when(!$user->isSuperAdmin(), fn (Builder $q) => $q->forUser($user))
            // Store badges should reflect pending workload only.
            ->whereIn('status', ['open', 'pending', 'overdue']);
        $this->applyFilters(
            $storeCountsQuery,
            $channel,
            $marketplaceId,
            0,
            $status,
            $dateFrom,
            $dateTo,
            $search,
            false
        );
        $storeCounts = $storeCountsQuery
            ->selectRaw('marketplace_store_id, COUNT(*) as aggregate')
            ->groupBy('marketplace_store_id')
            ->pluck('aggregate', 'marketplace_store_id');

        $marketplaces = Marketplace::query()->where('is_active', true)->orderBy('name')->get();
        $stores = MarketplaceStore::query()
            ->when(!$user->isSuperAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->where('is_active', true)
            ->orderBy('store_name')
            ->get();
        $templates = CommunicationTemplate::query()
            ->where('is_active', true)
            ->where(function ($q) use ($user): void {
                $q->whereNull('user_id');
                if ($user) {
                    $q->orWhere('user_id', $user->id);
                }
            })
            ->orderBy('title')
            ->get(['id', 'title', 'body']);

        return view('admin.communication-center.index', [
            'channel' => $channel,
            'threads' => $threads,
            'marketplaces' => $marketplaces,
            'stores' => $stores,
            'templates' => $templates,
            'filters' => compact('marketplaceId', 'storeId', 'status', 'dateFrom', 'dateTo', 'search'),
            'channelCounts' => $channelCounts,
            'marketplaceCounts' => $marketplaceCounts,
            'statusCounts' => $statusCounts,
            'storeCounts' => $storeCounts,
            'criticalThresholdMinutes' => $criticalThresholdMinutes,
        ]);
    }

    private function applyFilters(
        Builder $query,
        string $channel,
        int $marketplaceId,
        int $storeId,
        string $status,
        string $dateFrom,
        string $dateTo,
        string $search,
        bool $skipMarketplaceFilter = false
    ): void {
        $query->where('channel', $channel)
            ->when(!$skipMarketplaceFilter && $marketplaceId > 0, fn (Builder $q) => $q->where('marketplace_id', $marketplaceId))
            ->when($storeId > 0, fn (Builder $q) => $q->where('marketplace_store_id', $storeId))
            ->when($status !== '', fn (Builder $q) => $q->where('status', $status))
            ->when($dateFrom !== '', fn (Builder $q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn (Builder $q) => $q->whereDate('created_at', '<=', $dateTo))
            ->when($search !== '', function (Builder $q) use ($search): void {
                $q->where(function (Builder $searchQ) use ($search): void {
                    $searchQ->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('product_name', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            });
    }

    public function show(CommunicationThread $thread): View
    {
        $thread->load(['marketplace', 'marketplaceStore', 'messages.sentByUser', 'product', 'order']);
        $this->authorize('view', $thread);

        $user = SupportUser::currentUser();
        $templates = CommunicationTemplate::query()
            ->where('is_active', true)
            ->where(function ($q) use ($user): void {
                $q->whereNull('user_id');
                if ($user) {
                    $q->orWhere('user_id', $user->id);
                }
            })
            ->orderBy('title')
            ->get();

        return view('admin.communication-center.show', [
            'thread' => $thread,
            'templates' => $templates,
        ]);
    }

    public function reply(Request $request, CommunicationThread $thread, MarketplaceClientResolver $resolver): RedirectResponse|JsonResponse
    {
        $this->authorize('reply', $thread);
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'used_ai' => ['nullable', 'boolean'],
            'ai_template_id' => ['nullable', 'integer'],
            'ai_confidence' => ['nullable', 'integer', 'min:0', 'max:100'],
            'edit_message_id' => ['nullable', 'integer'],
        ]);

        $user = SupportUser::currentUser();
        abort_unless($user, 401);

        $replyResult = DB::transaction(function () use ($thread, $validated, $resolver, $user): array {
            $lockedThread = CommunicationThread::query()
                ->whereKey($thread->id)
                ->lockForUpdate()
                ->firstOrFail();

            $editMessageId = (int) ($validated['edit_message_id'] ?? 0);
            $editMessage = null;
            if ($editMessageId > 0) {
                $editMessage = CommunicationMessage::query()
                    ->where('id', $editMessageId)
                    ->where('thread_id', $lockedThread->id)
                    ->where('direction', 'outbound')
                    ->first();
                if (!$editMessage) {
                    throw ValidationException::withMessages([
                        'body' => 'Duzenlenecek mesaj bulunamadi.',
                    ]);
                }
            }

            $canReply = $lockedThread->last_inbound_at
                && (!$lockedThread->last_outbound_at || $lockedThread->last_outbound_at->lt($lockedThread->last_inbound_at));

            $canEditLastOutbound = false;
            if ($editMessage) {
                $lastOutboundId = CommunicationMessage::query()
                    ->where('thread_id', $lockedThread->id)
                    ->where('direction', 'outbound')
                    ->max('id');
                $canEditLastOutbound = ((int) $editMessage->id === (int) $lastOutboundId);
            }

            if (!$canReply && !$canEditLastOutbound) {
                throw ValidationException::withMessages([
                    'body' => 'Musteri yeni bir mesaj gondermeden tekrar yanit veremezsiniz.',
                ]);
            }

            $now = now();
            if ($canEditLastOutbound && $editMessage) {
                $meta = (array) ($editMessage->meta ?? []);
                $meta['edited'] = true;
                $meta['edited_by_user_id'] = (int) $user->id;
                $meta['edited_at'] = $now->toISOString();
                $meta['used_ai'] = (bool) ($validated['used_ai'] ?? false);
                $meta['ai_template_id'] = isset($validated['ai_template_id']) ? (int) $validated['ai_template_id'] : null;
                $meta['ai_confidence'] = isset($validated['ai_confidence']) ? (int) $validated['ai_confidence'] : null;

                $editMessage->forceFill([
                    'body' => $validated['body'],
                    'created_at_external' => $now,
                    'meta' => $meta,
                ])->save();

                $outboundMessage = $editMessage;
            } else {
                $outboundMessage = CommunicationMessage::query()->create([
                    'thread_id' => $lockedThread->id,
                    'direction' => 'outbound',
                    'body' => $validated['body'],
                    'created_at_external' => $now,
                    'sender_type' => 'seller',
                    'ai_suggested' => false,
                    'sent_by_user_id' => $user->id,
                    'meta' => [
                        'manual_reply' => true,
                        'used_ai' => (bool) ($validated['used_ai'] ?? false),
                        'ai_template_id' => isset($validated['ai_template_id']) ? (int) $validated['ai_template_id'] : null,
                        'ai_confidence' => isset($validated['ai_confidence']) ? (int) $validated['ai_confidence'] : null,
                    ],
                ]);
            }

            $lockedThread->forceFill([
                'status' => 'answered',
                'last_outbound_at' => $now,
            ])->save();

            $store = $lockedThread->marketplaceStore()->with('marketplace')->firstOrFail();
            $client = $resolver->resolve($store);
            $result = $client->sendReply($store, (string) $lockedThread->external_thread_id, $validated['body']);
            if (!($result['ok'] ?? false)) {
                Log::warning('communication_center.reply.remote_send_failed', [
                    'thread_id' => $lockedThread->id,
                    'store_id' => $store->id,
                    'external_thread_id' => $lockedThread->external_thread_id,
                    'result' => $result,
                ]);
            }

            return [
                'edited' => $canEditLastOutbound && $editMessage !== null,
                'message_id' => (int) $outboundMessage->id,
                'created_at' => $now,
            ];
        });

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => 'Yanit gonderildi.',
                'thread_id' => $thread->id,
                'channel' => $thread->channel,
                'body' => $validated['body'],
                'created_at' => $replyResult['created_at']->format('d.m.Y H:i'),
                'edited' => (bool) ($replyResult['edited'] ?? false),
                'message_id' => (int) ($replyResult['message_id'] ?? 0),
            ]);
        }

        return back()->with('success', 'Yanit gonderildi.');
    }

    public function aiSuggest(CommunicationThread $thread, CommunicationAiSuggestService $service): array
    {
        $this->authorize('reply', $thread);

        return $service->suggest($thread);
    }

    public function storeTemplate(Request $request): JsonResponse
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 401);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $template = CommunicationTemplate::query()->create([
            'user_id' => (int) $user->id,
            'category' => 'general',
            'title' => trim((string) $validated['title']),
            'body' => trim((string) $validated['body']),
            'marketplaces' => null,
            'is_active' => true,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Sablon olusturuldu.',
            'template' => [
                'id' => (int) $template->id,
                'title' => (string) $template->title,
                'body' => (string) $template->body,
            ],
        ]);
    }

    public function syncNow(): RedirectResponse
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 401);

        MarketplaceStore::query()
            ->when(!$user->isSuperAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->where('is_active', true)
            ->pluck('id')
            ->each(fn ($id) => SyncCommunicationJob::dispatch((int) $id));

        return back()->with('success', 'Senkronizasyon kuyruğa alındı.');
    }
}


