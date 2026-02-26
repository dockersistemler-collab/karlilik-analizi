<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ControlTowerDailySnapshot;
use App\Models\ControlTowerSignal;
use App\Services\ControlTower\ControlTowerAggregator;
use App\Services\ControlTower\ControlTowerCache;
use App\Services\ControlTower\SignalEngine;
use App\Support\SupportUser;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ControlTowerController extends Controller
{
    public function index(
        Request $request,
        ControlTowerAggregator $aggregator,
        SignalEngine $signalEngine,
        ControlTowerCache $cache
    ): View {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);
        $tenantId = (int) ($owner->tenant_id ?: $owner->id);

        $validated = $request->validate([
            'view' => ['nullable', 'in:cfo,ops'],
            'date' => ['nullable', 'date'],
            'range' => ['nullable', 'in:7d,30d'],
            'marketplace' => ['nullable', 'in:trendyol,hepsiburada,amazon,n11'],
            'severity' => ['nullable', 'in:info,warning,critical'],
            'refresh' => ['nullable', 'boolean'],
        ]);

        $view = (string) ($validated['view'] ?? 'cfo');
        $date = isset($validated['date']) ? CarbonImmutable::parse((string) $validated['date']) : CarbonImmutable::now()->subDay();
        $rangeDays = (($validated['range'] ?? '30d') === '7d') ? 7 : 30;
        $marketplace = isset($validated['marketplace']) ? strtolower((string) $validated['marketplace']) : null;
        $refresh = (bool) ($validated['refresh'] ?? false);

        if ($refresh) {
            $payload = $aggregator->aggregateDaily($tenantId, $date, $rangeDays, $marketplace);
            $signals = $signalEngine->generateSignals($tenantId, $date, $payload);
            $cache->put($tenantId, $date, $view, $rangeDays, $marketplace, $payload, $signals);
        } else {
            $snapshotPayload = $this->loadSnapshotPayload($tenantId, $date, $marketplace, $rangeDays);
            if ($snapshotPayload !== null) {
                $payload = $snapshotPayload;
                $signals = $this->loadSignals($tenantId, $date, $request);
                $cache->put($tenantId, $date, $view, $rangeDays, $marketplace, $payload, $signals);
            } else {
                $cached = $cache->remember($tenantId, $date, $view, $rangeDays, $marketplace, function () use ($aggregator, $signalEngine, $tenantId, $date, $rangeDays, $marketplace): array {
                    $payload = $aggregator->aggregateDaily($tenantId, $date, $rangeDays, $marketplace);
                    $signals = $signalEngine->generateSignals($tenantId, $date, $payload);
                    return ['payload' => $payload, 'signals' => $signals];
                });
                $payload = (array) ($cached['payload'] ?? []);
                $signals = (array) ($cached['signals'] ?? []);
            }
        }

        if (!$refresh && !isset($signals)) {
            $signals = $this->loadSignals($tenantId, $date, $request);
        }
        if (isset($validated['severity'])) {
            $signals = collect($signals)->where('severity', $validated['severity'])->values()->all();
        }

        return view('admin.control-tower.index', [
            'viewMode' => $view,
            'date' => $date,
            'range' => $rangeDays === 7 ? '7d' : '30d',
            'marketplace' => $marketplace,
            'payload' => $payload,
            'signals' => $signals,
        ]);
    }

    public function signals(Request $request): View
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);
        $tenantId = (int) ($owner->tenant_id ?: $owner->id);

        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'type' => ['nullable', 'string', 'max:64'],
            'severity' => ['nullable', 'in:info,warning,critical'],
            'marketplace' => ['nullable', 'in:trendyol,hepsiburada,amazon,n11'],
        ]);
        $date = isset($validated['date']) ? CarbonImmutable::parse((string) $validated['date']) : CarbonImmutable::now()->subDay();

        $rows = collect($this->querySignals($tenantId, $date, $validated)->get()->all());

        return view('admin.control-tower.signals', [
            'date' => $date,
            'signals' => $rows,
            'filters' => $validated,
        ]);
    }

    public function resolveSignal(Request $request, ControlTowerSignal $signal): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);
        $tenantId = (int) ($owner->tenant_id ?: $owner->id);
        abort_if((int) $signal->tenant_id !== $tenantId, 403);

        $signal->update([
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Sinyal cozuldu olarak isaretlendi.');
    }

    public function drillProfitLeak(Request $request): View
    {
        return $this->drillView($request, 'PROFIT_LEAK', 'Kâr Sızıntısı Detay');
    }

    public function drillBuybox(Request $request): View
    {
        return $this->drillView($request, 'BUYBOX_LOSS', 'BuyBox Detay');
    }

    public function drillRisk(Request $request): View
    {
        return $this->drillView($request, null, 'Risk Detay', ['STORE_SCORE_DROP', 'SHIPPING_SLA', 'RETURN_SPIKE']);
    }

    public function drillCampaigns(Request $request): View
    {
        return $this->drillView($request, null, 'Kampanya/Şok Detay', ['CAMPAIGN_EROSION', 'ALGO_SHIFT', 'FEE_DRIFT']);
    }

    public function drillActions(Request $request): View
    {
        return $this->drillView($request, null, 'Aksiyon Detay');
    }

    /**
     * @param array<int,string> $types
     */
    private function drillView(Request $request, ?string $singleType, string $title, array $types = []): View
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);
        $tenantId = (int) ($owner->tenant_id ?: $owner->id);
        $date = $request->filled('date')
            ? CarbonImmutable::parse((string) $request->input('date'))
            : CarbonImmutable::now()->subDay();

        $query = ControlTowerSignal::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', $date->toDateString())
            ->latest('severity')
            ->latest('id');

        if ($singleType !== null) {
            $query->where('type', $singleType);
        } elseif ($types !== []) {
            $query->whereIn('type', $types);
        }

        return view('admin.control-tower.drilldown', [
            'title' => $title,
            'date' => $date,
            'signals' => Schema::hasTable('control_tower_signals') ? $query->get() : collect(),
        ]);
    }

    /**
     * @return array<string,mixed>|null
     */
    private function loadSnapshotPayload(int $tenantId, CarbonImmutable $date, ?string $marketplace, int $rangeDays): ?array
    {
        if (!Schema::hasTable('control_tower_daily_snapshots')) {
            return null;
        }
        if ($marketplace !== null || $rangeDays !== 30) {
            return null;
        }

        $row = ControlTowerDailySnapshot::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', $date->toDateString())
            ->first();

        return is_array($row?->payload) ? $row->payload : null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function loadSignals(int $tenantId, CarbonImmutable $date, Request $request): array
    {
        if (!Schema::hasTable('control_tower_signals')) {
            return [];
        }

        $filters = array_filter([
            'type' => $request->input('type'),
            'severity' => $request->input('severity'),
            'marketplace' => $request->input('marketplace'),
        ], fn ($v) => $v !== null && $v !== '');

        return $this->querySignals($tenantId, $date, $filters)->get()->map(fn (ControlTowerSignal $row) => $row->toArray())->all();
    }

    private function querySignals(int $tenantId, CarbonImmutable $date, array $filters)
    {
        return ControlTowerSignal::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', $date->toDateString())
            ->when(isset($filters['type']), fn ($q) => $q->where('type', (string) $filters['type']))
            ->when(isset($filters['severity']), fn ($q) => $q->where('severity', (string) $filters['severity']))
            ->when(isset($filters['marketplace']), fn ($q) => $q->where('marketplace', strtolower((string) $filters['marketplace'])))
            ->latest('id');
    }
}
