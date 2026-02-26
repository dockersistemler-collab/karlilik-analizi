<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActionEngineRun;
use App\Models\ActionRecommendation;
use App\Models\BuyBoxScore;
use App\Models\BuyBoxScoringProfile;
use App\Models\ControlTowerDailySnapshot;
use App\Models\ControlTowerSignal;
use App\Models\MarketplaceRiskProfile;
use App\Models\MarketplaceRiskScore;
use App\Models\MarketplaceOfferSnapshot;
use App\Models\Module;
use App\Models\OrderProfitSnapshot;
use App\Models\ProfitCostProfile;
use App\Models\User;
use App\Models\UserModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class IntelligenceModuleController extends Controller
{
    private const MODULE_DEFAULTS = [
        'profit_engine' => [
            'name' => 'Profit Engine',
            'description' => 'Order-level profitability engine module.',
            'type' => 'feature',
            'billing_type' => 'recurring',
        ],
        'marketplace_risk' => [
            'name' => 'Marketplace Risk',
            'description' => 'Marketplace risk scoring and alert module.',
            'type' => 'feature',
            'billing_type' => 'recurring',
        ],
        'action_engine' => [
            'name' => 'Action Engine',
            'description' => 'Action recommendation engine module.',
            'type' => 'feature',
            'billing_type' => 'recurring',
        ],
        'buybox_engine' => [
            'name' => 'BuyBox Engine',
            'description' => 'BuyBox snapshots and score tracking module.',
            'type' => 'feature',
            'billing_type' => 'recurring',
        ],
        'feature.control_tower' => [
            'name' => 'Marketplace Intelligence Control Tower',
            'description' => 'CFO + OPS unified control tower module.',
            'type' => 'feature',
            'billing_type' => 'recurring',
        ],
    ];

    public function profitSettings(Request $request): View
    {
        $module = $this->resolveIntelligenceModule('profit_engine');
        $hasSnapshotsTable = Schema::hasTable('order_profit_snapshots');
        $hasProfilesTable = Schema::hasTable('profit_cost_profiles');

        $stats = [
            'snapshots' => $hasSnapshotsTable ? OrderProfitSnapshot::query()->count() : 0,
            'profiles' => $hasProfilesTable ? ProfitCostProfile::query()->count() : 0,
            'active_clients' => $this->activeClientCountFor('profit_engine'),
        ];

        $topTenants = $hasSnapshotsTable
            ? OrderProfitSnapshot::query()
                ->selectRaw('tenant_id, COUNT(*) as total_orders, COALESCE(SUM(net_profit),0) as total_profit')
                ->groupBy('tenant_id')
                ->orderByDesc('total_orders')
                ->limit(15)
                ->get()
            : collect();

        return view('super-admin.intelligence.profit-settings', compact('module', 'stats', 'topTenants'));
    }

    public function riskProfiles(Request $request): View
    {
        $module = $this->resolveIntelligenceModule('marketplace_risk');
        $hasProfilesTable = Schema::hasTable('marketplace_risk_profiles');
        $hasScoresTable = Schema::hasTable('marketplace_risk_scores');

        $stats = [
            'profiles' => $hasProfilesTable ? MarketplaceRiskProfile::query()->count() : 0,
            'risk_scores' => $hasScoresTable ? MarketplaceRiskScore::query()->count() : 0,
            'active_clients' => $this->activeClientCountFor('marketplace_risk'),
        ];

        $profiles = $hasProfilesTable
            ? MarketplaceRiskProfile::query()
                ->when($request->filled('marketplace'), fn ($q) => $q->where('marketplace', strtolower((string) $request->input('marketplace'))))
                ->latest('id')
                ->paginate(30)
                ->withQueryString()
            : new LengthAwarePaginator(
                items: [],
                total: 0,
                perPage: 30,
                currentPage: 1,
                options: [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

        return view('super-admin.intelligence.risk-profiles', compact('module', 'stats', 'profiles'));
    }

    public function actionRules(): View
    {
        $module = $this->resolveIntelligenceModule('action_engine');
        $hasRecommendationsTable = Schema::hasTable('action_recommendations');
        $hasRunsTable = Schema::hasTable('action_engine_runs');

        $stats = [
            'recommendations' => $hasRecommendationsTable ? ActionRecommendation::query()->count() : 0,
            'open' => $hasRecommendationsTable ? ActionRecommendation::query()->where('status', 'open')->count() : 0,
            'active_clients' => $this->activeClientCountFor('action_engine'),
        ];

        $byActionType = $hasRecommendationsTable
            ? ActionRecommendation::query()
                ->selectRaw('action_type, COUNT(*) as total')
                ->groupBy('action_type')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
            : collect();
        $latestRuns = $hasRunsTable
            ? ActionEngineRun::query()->latest('run_date')->limit(20)->get()
            : collect();

        return view('super-admin.intelligence.action-rules', compact('module', 'stats', 'byActionType', 'latestRuns'));
    }

    public function buyboxEngine(Request $request): View
    {
        $module = $this->resolveIntelligenceModule('buybox_engine');
        $hasSnapshotsTable = Schema::hasTable('marketplace_offer_snapshots');
        $hasScoresTable = Schema::hasTable('buybox_scores');
        $hasProfilesTable = Schema::hasTable('buybox_scoring_profiles');

        $stats = [
            'snapshots' => $hasSnapshotsTable ? MarketplaceOfferSnapshot::query()->count() : 0,
            'scores' => $hasScoresTable ? BuyBoxScore::query()->count() : 0,
            'profiles' => $hasProfilesTable ? BuyBoxScoringProfile::query()->count() : 0,
            'active_clients' => $this->activeClientCountFor('buybox_engine'),
        ];

        $latestScores = $hasScoresTable
            ? BuyBoxScore::query()
                ->latest('date')
                ->latest('id')
                ->limit(20)
                ->get()
            : collect();

        return view('super-admin.intelligence.buybox-engine', compact('module', 'stats', 'latestScores'));
    }

    public function controlTower(Request $request): View
    {
        $module = $this->resolveIntelligenceModule('feature.control_tower');
        $hasSnapshotsTable = Schema::hasTable('control_tower_daily_snapshots');
        $hasSignalsTable = Schema::hasTable('control_tower_signals');

        $stats = [
            'snapshots' => $hasSnapshotsTable ? ControlTowerDailySnapshot::query()->count() : 0,
            'signals' => $hasSignalsTable ? ControlTowerSignal::query()->count() : 0,
            'critical_open' => $hasSignalsTable ? ControlTowerSignal::query()->where('severity', 'critical')->where('is_resolved', false)->count() : 0,
            'active_clients' => $this->activeClientCountFor('feature.control_tower'),
        ];

        $latestSnapshots = $hasSnapshotsTable
            ? ControlTowerDailySnapshot::query()->latest('date')->latest('id')->limit(20)->get()
            : collect();

        $latestSignals = $hasSignalsTable
            ? ControlTowerSignal::query()->latest('date')->latest('id')->limit(20)->get()
            : collect();

        $clients = User::query()
            ->where('role', 'client')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $userModules = UserModule::query()
            ->with('user:id,name,email')
            ->where('module_id', $module->id)
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('super-admin.intelligence.control-tower', compact('module', 'stats', 'latestSnapshots', 'latestSignals', 'clients', 'userModules'));
    }

    public function toggle(Request $request, string $code): RedirectResponse
    {
        abort_unless(in_array($code, ['profit_engine', 'marketplace_risk', 'action_engine', 'buybox_engine', 'feature.control_tower'], true), 404);
        $module = $this->resolveIntelligenceModule($code);
        $module->update([
            'is_active' => !$module->is_active,
        ]);

        return back()->with('success', 'Modul durumu guncellendi.');
    }

    private function activeClientCountFor(string $code): int
    {
        return UserModule::query()
            ->where('status', 'active')
            ->whereHas('module', fn ($q) => $q->where('code', $code))
            ->distinct('user_id')
            ->count('user_id');
    }

    private function resolveIntelligenceModule(string $code): Module
    {
        abort_unless(array_key_exists($code, self::MODULE_DEFAULTS), 404);
        $defaults = self::MODULE_DEFAULTS[$code];

        return Module::query()->firstOrCreate(
            ['code' => $code],
            [
                'name' => $defaults['name'],
                'description' => $defaults['description'],
                'type' => $defaults['type'],
                'billing_type' => $defaults['billing_type'],
                'is_active' => true,
                'sort_order' => 0,
            ]
        );
    }
}
