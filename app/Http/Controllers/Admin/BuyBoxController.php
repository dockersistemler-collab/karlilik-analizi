<?php

namespace App\Http\Controllers\Admin;

use App\Jobs\CalculateBuyBoxScoresJob;
use App\Models\BuyBoxScore;
use App\Models\BuyBoxScoringProfile;
use App\Http\Controllers\Controller;
use App\Jobs\CollectBuyBoxSnapshotsJob;
use App\Models\ActionRecommendation;
use App\Models\MarketplaceOfferSnapshot;
use App\Services\ActionEngine\ActionEngine;
use App\Services\Modules\ModuleGate;
use App\Support\SupportUser;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BuyBoxController extends Controller
{
    /** @var array<int,string> */
    private const BUYBOX_ACTION_TYPES = [
        'PRICE_ADJUST',
        'SHIPPING_SLA_FIX',
        'STOCK_FIX',
        'LISTING_OPTIMIZE',
    ];

    public function index(Request $request): View
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);

        $tenantId = (int) ($owner->tenant_id ?: $owner->id);
        $marketplace = strtolower(trim((string) $request->query('marketplace', '')));
        $sku = trim((string) $request->query('sku', ''));
        $date = $request->filled('date') ? Carbon::parse((string) $request->query('date'))->toDateString() : null;
        $isWinning = $request->query('is_winning');

        $snapshots = MarketplaceOfferSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->when($marketplace !== '', fn ($q) => $q->where('marketplace', $marketplace))
            ->when($sku !== '', fn ($q) => $q->where('sku', 'like', "%{$sku}%"))
            ->when($date !== null, fn ($q) => $q->whereDate('date', $date))
            ->when($isWinning !== null && $isWinning !== '', fn ($q) => $q->where('is_winning', (bool) $isWinning))
            ->orderByDesc('date')
            ->orderBy('marketplace')
            ->orderBy('sku')
            ->paginate(30)
            ->withQueryString();

        return view('admin.buybox.index', compact('snapshots'));
    }

    public function importCsv(Request $request): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $tenantId = (int) ($owner->tenant_id ?: $owner->id);
        $handle = fopen($validated['file']->getRealPath(), 'rb');
        if (!$handle) {
            return back()->with('error', 'CSV dosyasi okunamadi.');
        }

        $header = fgetcsv($handle);
        if (!is_array($header)) {
            fclose($handle);
            return back()->with('error', 'CSV baslik satiri okunamadi.');
        }

        $map = [];
        foreach ($header as $i => $name) {
            $map[strtolower(trim((string) $name))] = $i;
        }

        foreach (['date', 'marketplace', 'sku'] as $required) {
            if (!array_key_exists($required, $map)) {
                fclose($handle);
                return back()->with('error', "CSV kolon eksik: {$required}");
            }
        }

        while (($row = fgetcsv($handle)) !== false) {
            $date = trim((string) ($row[$map['date']] ?? ''));
            $marketplace = strtolower(trim((string) ($row[$map['marketplace']] ?? '')));
            $sku = trim((string) ($row[$map['sku']] ?? ''));
            if ($date === '' || $marketplace === '' || $sku === '') {
                continue;
            }

            MarketplaceOfferSnapshot::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'marketplace' => $marketplace,
                    'date' => $date,
                    'sku' => $sku,
                ],
                [
                    'is_winning' => $this->csvBool($row, $map, 'is_winning') ?? false,
                    'position_rank' => $this->csvInt($row, $map, 'position_rank'),
                    'our_price' => $this->csvFloat($row, $map, 'our_price'),
                    'competitor_best_price' => $this->csvFloat($row, $map, 'competitor_best_price'),
                    'store_score' => $this->csvFloat($row, $map, 'store_score'),
                    'stock_available' => $this->csvInt($row, $map, 'stock_available'),
                    'source' => 'csv',
                    'meta' => ['import' => 'csv'],
                ]
            );
        }

        fclose($handle);

        return back()->with('success', 'BuyBox CSV import tamamlandi.');
    }

    public function exportCsv(Request $request)
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);

        $tenantId = (int) ($owner->tenant_id ?: $owner->id);
        $marketplace = strtolower(trim((string) $request->query('marketplace', '')));
        $date = $request->filled('date') ? Carbon::parse((string) $request->query('date'))->toDateString() : null;

        $rows = MarketplaceOfferSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->when($marketplace !== '', fn ($q) => $q->where('marketplace', $marketplace))
            ->when($date !== null, fn ($q) => $q->whereDate('date', $date))
            ->orderByDesc('date')
            ->orderBy('marketplace')
            ->orderBy('sku')
            ->get();

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, [
                'date',
                'marketplace',
                'sku',
                'is_winning',
                'position_rank',
                'our_price',
                'competitor_best_price',
                'store_score',
                'stock_available',
                'source',
            ]);

            foreach ($rows as $row) {
                fputcsv($out, [
                    optional($row->date)->toDateString(),
                    $row->marketplace,
                    $row->sku,
                    $row->is_winning ? 1 : 0,
                    $row->position_rank,
                    $row->our_price,
                    $row->competitor_best_price,
                    $row->store_score,
                    $row->stock_available,
                    $row->source,
                ]);
            }
            fclose($out);
        }, 'buybox-snapshots.csv', ['Content-Type' => 'text/csv']);
    }

    public function collect(Request $request): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);

        $validated = $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        $tenantId = (int) ($owner->tenant_id ?: $owner->id);
        $date = (string) ($validated['date'] ?? now()->subDay()->toDateString());
        CollectBuyBoxSnapshotsJob::dispatch($tenantId, $date);

        return back()->with('success', 'BuyBox toplama gorevi kuyruga alindi.');
    }

    public function scores(Request $request): View
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);

        $tenantId = (int) ($owner->tenant_id ?: $owner->id);
        $marketplace = strtolower(trim((string) $request->query('marketplace', '')));
        $dateFrom = $request->filled('date_from') ? Carbon::parse((string) $request->query('date_from'))->toDateString() : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse((string) $request->query('date_to'))->toDateString() : null;
        $status = strtolower(trim((string) $request->query('status', '')));
        $minStoreScore = $request->filled('min_store_score') ? (float) $request->query('min_store_score') : null;

        $scores = BuyBoxScore::query()
            ->with('snapshot:id,store_score,our_price,competitor_best_price')
            ->where('tenant_id', $tenantId)
            ->when($marketplace !== '', fn ($q) => $q->where('marketplace', $marketplace))
            ->when($dateFrom !== null, fn ($q) => $q->whereDate('date', '>=', $dateFrom))
            ->when($dateTo !== null, fn ($q) => $q->whereDate('date', '<=', $dateTo))
            ->when(in_array($status, ['winning', 'losing', 'risky'], true), fn ($q) => $q->where('status', $status))
            ->when($minStoreScore !== null, fn ($q) => $q->whereHas('snapshot', fn ($s) => $s->where('store_score', '>=', $minStoreScore)))
            ->orderByDesc('date')
            ->orderBy('marketplace')
            ->paginate(30)
            ->withQueryString();

        $impactSummary = $this->buildBuyBoxImpactSummary(
            $tenantId,
            $marketplace !== '' ? $marketplace : null,
            null,
            $dateFrom,
            $dateTo
        );

        return view('admin.buybox.scores', compact('scores', 'impactSummary'));
    }

    public function scoreProfiles(Request $request): View
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);

        $tenantId = (int) ($owner->tenant_id ?: $owner->id);
        $profiles = BuyBoxScoringProfile::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('marketplace')
            ->get();

        return view('admin.buybox.profiles', compact('profiles'));
    }

    public function storeScoreProfile(Request $request): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);

        $validated = $request->validate([
            'marketplace' => ['required', 'in:trendyol,hepsiburada,amazon,n11'],
            'weights' => ['required', 'string'],
            'thresholds' => ['required', 'string'],
        ]);

        $tenantId = (int) ($owner->tenant_id ?: $owner->id);
        $weights = json_decode($validated['weights'], true);
        $thresholds = json_decode($validated['thresholds'], true);
        if (!is_array($weights) || !is_array($thresholds)) {
            return back()->with('error', 'weights/thresholds JSON gecersiz.');
        }

        BuyBoxScoringProfile::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'marketplace' => strtolower($validated['marketplace']),
            ],
            [
                'weights' => $weights,
                'thresholds' => $thresholds,
            ]
        );

        return back()->with('success', 'Skorlama profili kaydedildi.');
    }

    public function updateScoreProfile(Request $request, BuyBoxScoringProfile $profile): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);
        $tenantId = (int) ($owner->tenant_id ?: $owner->id);
        abort_if((int) $profile->tenant_id !== $tenantId, 403);

        $validated = $request->validate([
            'weights' => ['required', 'string'],
            'thresholds' => ['required', 'string'],
        ]);

        $weights = json_decode($validated['weights'], true);
        $thresholds = json_decode($validated['thresholds'], true);
        if (!is_array($weights) || !is_array($thresholds)) {
            return back()->with('error', 'weights/thresholds JSON gecersiz.');
        }

        $profile->update([
            'weights' => $weights,
            'thresholds' => $thresholds,
        ]);

        return back()->with('success', 'Skorlama profili guncellendi.');
    }

    public function calculateScores(Request $request): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);

        $validated = $request->validate([
            'date' => ['nullable', 'date'],
        ]);
        $tenantId = (int) ($owner->tenant_id ?: $owner->id);
        $date = (string) ($validated['date'] ?? now()->subDay()->toDateString());
        CalculateBuyBoxScoresJob::dispatch($tenantId, $date);

        return back()->with('success', 'BuyBox score hesaplama gorevi kuyruga alindi.');
    }

    public function suggestActions(Request $request, ActionEngine $engine, ModuleGate $moduleGate): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);
        if (!$moduleGate->isEnabledForUser($owner, 'action_engine')) {
            return back()->with('error', 'Aksiyon onerileri icin Action Engine modulu aktif olmali.');
        }

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'marketplace' => ['required', 'in:trendyol,hepsiburada,amazon,n11'],
            'sku' => ['required', 'string', 'max:191'],
        ]);

        $tenantId = (int) ($owner->tenant_id ?: $owner->id);
        $stats = $engine->runBuyBoxRulesForDate(
            $tenantId,
            (int) $owner->id,
            CarbonImmutable::parse((string) $validated['date']),
            strtolower((string) $validated['marketplace']),
            trim((string) $validated['sku'])
        );

        return back()->with(
            'success',
            sprintf(
                'Aksiyon onerileri calisti. generated=%d updated=%d skipped=%d',
                (int) ($stats['generated'] ?? 0),
                (int) ($stats['updated'] ?? 0),
                (int) ($stats['skipped'] ?? 0)
            )
        );
    }

    public function detail(Request $request): View
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);

        $validated = $request->validate([
            'marketplace' => ['required', 'in:trendyol,hepsiburada,amazon,n11'],
            'sku' => ['required', 'string', 'max:191'],
        ]);

        $tenantId = (int) ($owner->tenant_id ?: $owner->id);
        $marketplace = strtolower((string) $validated['marketplace']);
        $sku = trim((string) $validated['sku']);

        $trend = BuyBoxScore::query()
            ->with('snapshot:id,store_score,our_price,competitor_best_price')
            ->where('tenant_id', $tenantId)
            ->where('marketplace', $marketplace)
            ->where('sku', $sku)
            ->orderBy('date')
            ->limit(30)
            ->get();

        $latest = $trend->last();

        $recommendations = ActionRecommendation::query()
            ->with('impact:id,recommendation_id,delta,confidence')
            ->where('tenant_id', $tenantId)
            ->where('marketplace', $marketplace)
            ->where('sku', $sku)
            ->latest('date')
            ->latest('id')
            ->limit(25)
            ->get();

        $impactSummary = $this->buildBuyBoxImpactSummary($tenantId, $marketplace, $sku);

        return view('admin.buybox.detail', compact('marketplace', 'sku', 'trend', 'latest', 'recommendations', 'impactSummary'));
    }

    private function buildBuyBoxImpactSummary(
        int $tenantId,
        ?string $marketplace = null,
        ?string $sku = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        $recommendations = ActionRecommendation::query()
            ->with('impact:id,recommendation_id,delta,confidence')
            ->where('tenant_id', $tenantId)
            ->whereIn('action_type', self::BUYBOX_ACTION_TYPES)
            ->when($marketplace !== null && $marketplace !== '', fn ($q) => $q->where('marketplace', $marketplace))
            ->when($sku !== null && $sku !== '', fn ($q) => $q->where('sku', $sku))
            ->when($dateFrom !== null, fn ($q) => $q->whereDate('date', '>=', $dateFrom))
            ->when($dateTo !== null, fn ($q) => $q->whereDate('date', '<=', $dateTo))
            ->whereHas('impact')
            ->get(['id', 'status', 'date']);

        $winProbabilityDeltas = $recommendations
            ->map(fn (ActionRecommendation $row) => $this->impactDeltaAsFloat($row, 'win_probability'))
            ->filter(fn ($value) => $value !== null)
            ->values();

        $netProfitDeltas = $recommendations
            ->map(fn (ActionRecommendation $row) => $this->impactDeltaAsFloat($row, 'net_profit'))
            ->filter(fn ($value) => $value !== null)
            ->values();

        $confidences = $recommendations
            ->map(function (ActionRecommendation $row): ?float {
                if (!$row->impact) {
                    return null;
                }

                return is_numeric($row->impact->confidence)
                    ? (float) $row->impact->confidence
                    : null;
            })
            ->filter(fn ($value) => $value !== null)
            ->values();

        return [
            'recommendation_count' => $recommendations->count(),
            'open_count' => $recommendations->where('status', 'open')->count(),
            'win_probability_samples' => $winProbabilityDeltas->count(),
            'net_profit_samples' => $netProfitDeltas->count(),
            'avg_win_probability_delta_pp' => $winProbabilityDeltas->isNotEmpty()
                ? ((float) $winProbabilityDeltas->avg()) * 100
                : null,
            'sum_net_profit_delta' => $netProfitDeltas->isNotEmpty() ? (float) $netProfitDeltas->sum() : null,
            'avg_net_profit_delta' => $netProfitDeltas->isNotEmpty() ? (float) $netProfitDeltas->avg() : null,
            'avg_confidence' => $confidences->isNotEmpty() ? (float) $confidences->avg() : null,
        ];
    }

    private function impactDeltaAsFloat(ActionRecommendation $recommendation, string $key): ?float
    {
        $value = data_get($recommendation->impact?->delta, $key);
        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function csvFloat(array $row, array $map, string $key): ?float
    {
        if (!array_key_exists($key, $map)) {
            return null;
        }

        $value = trim((string) ($row[$map[$key]] ?? ''));
        if ($value === '') {
            return null;
        }

        return (float) str_replace(',', '.', $value);
    }

    private function csvInt(array $row, array $map, string $key): ?int
    {
        $value = $this->csvFloat($row, $map, $key);
        return $value === null ? null : (int) $value;
    }

    private function csvBool(array $row, array $map, string $key): ?bool
    {
        if (!array_key_exists($key, $map)) {
            return null;
        }

        $value = strtolower(trim((string) ($row[$map[$key]] ?? '')));
        if ($value === '') {
            return null;
        }

        return in_array($value, ['1', 'true', 'evet', 'yes'], true);
    }
}
