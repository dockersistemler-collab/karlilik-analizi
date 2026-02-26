<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActionRecommendation;
use App\Models\Marketplace;
use App\Models\MarketplaceRiskScore;
use App\Models\OrderProfitSnapshot;
use App\Services\Entitlements\EntitlementService;
use App\Services\Modules\ModuleGate;
use App\Support\SupportUser;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DecisionCenterController extends Controller
{
    public function index(Request $request, EntitlementService $entitlements, ModuleGate $moduleGate): View
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);

        $tenantId = (int) ($owner->tenant_id ?: $owner->id);
        $marketplace = strtolower(trim((string) $request->input('marketplace', '')));
        $sku = trim((string) $request->input('sku', ''));

        $dateFrom = $request->filled('date_from')
            ? CarbonImmutable::parse((string) $request->input('date_from'))
            : CarbonImmutable::now()->subDays(13);
        $dateTo = $request->filled('date_to')
            ? CarbonImmutable::parse((string) $request->input('date_to'))
            : CarbonImmutable::now();
        if ($dateTo->lessThan($dateFrom)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $hasProfitModule = $moduleGate->isActive('profit_engine') && $entitlements->hasModule($owner, 'profit_engine');
        $hasRiskModule = $moduleGate->isActive('marketplace_risk') && $entitlements->hasModule($owner, 'marketplace_risk');
        $hasActionModule = $moduleGate->isActive('action_engine') && $entitlements->hasModule($owner, 'action_engine');

        $profit = $this->profitMetrics($tenantId, $owner->id, $marketplace, $dateFrom, $dateTo, $hasProfitModule);
        $risk = $this->riskMetrics($tenantId, $marketplace, $dateFrom, $dateTo, $hasRiskModule);
        $action = $this->actionMetrics($tenantId, $marketplace, $sku, $dateFrom, $dateTo, $hasActionModule);

        $global = [
            'net_profit_total' => (float) $profit['net_profit_total'],
            'avg_risk_score' => (float) $risk['avg_risk_score'],
            'open_actions' => (int) $action['open'],
            'critical_risk_count' => (int) $risk['critical_count'],
        ];

        $ctaCards = [];
        if ($hasRiskModule && $risk['critical_count'] > 0) {
            $ctaCards[] = [
                'title' => 'Kritik Riskler',
                'text' => $risk['critical_count'].' pazaryeri kaydinda kritik risk var.',
                'href' => route('portal.marketplace-risk.index', ['marketplace' => $marketplace !== '' ? $marketplace : null]),
                'button' => 'Kritikleri Gor',
                'tone' => 'critical',
            ];
        }
        if ($hasActionModule && $action['high_open'] > 0) {
            $ctaCards[] = [
                'title' => 'Yuksek Oncelikli Oneriler',
                'text' => $action['high_open'].' adet high/critical open oneri var.',
                'href' => route('portal.action-engine.index', ['status' => 'open', 'marketplace' => $marketplace !== '' ? $marketplace : null]),
                'button' => 'Bugun Onerileri Uygula',
                'tone' => 'warning',
            ];
        }
        if ($hasProfitModule && ($profit['missing_rule_count'] > 0 || $profit['missing_cost_count'] > 0)) {
            $ctaCards[] = [
                'title' => 'Eksik Hesap Parametreleri',
                'text' => 'Eksik kural: '.$profit['missing_rule_count'].' | Eksik maliyet: '.$profit['missing_cost_count'],
                'href' => route('portal.profit-engine.index', ['rule_missing' => 1, 'marketplace' => $marketplace !== '' ? $marketplace : null]),
                'button' => 'Eksik Kurali Tamamla',
                'tone' => 'info',
            ];
        }
        if ($hasProfitModule && (float) $profit['net_profit_total'] < 0) {
            $ctaCards[] = [
                'title' => 'Net Kar Negatif',
                'text' => 'Secili donemde toplam net kar negatifte.',
                'href' => route('portal.action-engine.index', ['status' => 'open', 'marketplace' => $marketplace !== '' ? $marketplace : null]),
                'button' => 'Negatif Kara Aksiyon Al',
                'tone' => 'critical',
            ];
        }

        $marketplaces = Marketplace::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.decision-center.index', compact(
            'marketplaces',
            'marketplace',
            'sku',
            'dateFrom',
            'dateTo',
            'hasProfitModule',
            'hasRiskModule',
            'hasActionModule',
            'profit',
            'risk',
            'action',
            'global',
            'ctaCards'
        ));
    }

    private function profitMetrics(
        int $tenantId,
        int $ownerId,
        string $marketplace,
        CarbonImmutable $dateFrom,
        CarbonImmutable $dateTo,
        bool $enabled
    ): array {
        if (!$enabled) {
            return [
                'net_profit_total' => 0,
                'avg_margin' => 0,
                'missing_rule_count' => 0,
                'missing_cost_count' => 0,
                'rows_count' => 0,
                'trend' => collect(),
                'top_negative_orders' => collect(),
            ];
        }

        $base = OrderProfitSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $ownerId)
            ->whereBetween('calculated_at', [$dateFrom->startOfDay(), $dateTo->endOfDay()])
            ->when($marketplace !== '', fn ($q) => $q->where('marketplace', $marketplace));

        $totals = (clone $base)
            ->selectRaw('COALESCE(SUM(net_profit),0) as net_profit_total, COALESCE(AVG(net_margin),0) as avg_margin, COUNT(*) as rows_count')
            ->first();

        $missingRuleCount = (clone $base)->where('meta->rule_missing', true)->count();
        $missingCostCount = (clone $base)->whereJsonLength('meta->cost_missing_skus', '>', 0)->count();

        $trend = (clone $base)
            ->selectRaw('DATE(calculated_at) as day, COALESCE(SUM(net_profit),0) as net_profit, COALESCE(AVG(net_margin),0) as net_margin')
            ->groupBy(DB::raw('DATE(calculated_at)'))
            ->orderBy('day')
            ->get();

        $topNegativeOrders = (clone $base)
            ->with('order')
            ->orderBy('net_profit')
            ->limit(5)
            ->get(['id', 'order_id', 'marketplace', 'net_profit', 'net_margin', 'calculated_at']);

        return [
            'net_profit_total' => (float) ($totals->net_profit_total ?? 0),
            'avg_margin' => (float) ($totals->avg_margin ?? 0),
            'rows_count' => (int) ($totals->rows_count ?? 0),
            'missing_rule_count' => $missingRuleCount,
            'missing_cost_count' => $missingCostCount,
            'trend' => $trend,
            'top_negative_orders' => $topNegativeOrders,
        ];
    }

    private function riskMetrics(
        int $tenantId,
        string $marketplace,
        CarbonImmutable $dateFrom,
        CarbonImmutable $dateTo,
        bool $enabled
    ): array {
        if (!$enabled) {
            return [
                'avg_risk_score' => 0,
                'warning_count' => 0,
                'critical_count' => 0,
                'latest' => null,
                'trend' => collect(),
                'top_drivers' => [],
            ];
        }

        $base = MarketplaceRiskScore::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->when($marketplace !== '', fn ($q) => $q->where('marketplace', $marketplace));

        $summary = (clone $base)->selectRaw(
            'COALESCE(AVG(risk_score),0) as avg_risk_score,
            SUM(CASE WHEN status = "warning" THEN 1 ELSE 0 END) as warning_count,
            SUM(CASE WHEN status = "critical" THEN 1 ELSE 0 END) as critical_count'
        )->first();

        $latest = (clone $base)->latest('date')->latest('id')->first();
        $trend = (clone $base)
            ->selectRaw('date as day, COALESCE(AVG(risk_score),0) as risk_score, SUM(CASE WHEN status = "critical" THEN 1 ELSE 0 END) as critical_count')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $drivers = [];
        foreach ((clone $base)->whereIn('status', ['warning', 'critical'])->limit(200)->get(['reasons']) as $row) {
            foreach ((array) data_get($row->reasons, 'drivers', []) as $driver) {
                $metric = (string) data_get($driver, 'metric', '');
                if ($metric === '') {
                    continue;
                }
                $drivers[$metric] = ($drivers[$metric] ?? 0) + 1;
            }
        }
        arsort($drivers);

        return [
            'avg_risk_score' => (float) ($summary->avg_risk_score ?? 0),
            'warning_count' => (int) ($summary->warning_count ?? 0),
            'critical_count' => (int) ($summary->critical_count ?? 0),
            'latest' => $latest,
            'trend' => $trend,
            'top_drivers' => array_slice($drivers, 0, 3, true),
        ];
    }

    private function actionMetrics(
        int $tenantId,
        string $marketplace,
        string $sku,
        CarbonImmutable $dateFrom,
        CarbonImmutable $dateTo,
        bool $enabled
    ): array {
        if (!$enabled) {
            return [
                'open' => 0,
                'applied' => 0,
                'dismissed' => 0,
                'high_open' => 0,
                'trend' => collect(),
                'latest_open' => collect(),
            ];
        }

        $base = ActionRecommendation::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->when($marketplace !== '', fn ($q) => $q->where('marketplace', $marketplace))
            ->when($sku !== '', fn ($q) => $q->where('sku', $sku));

        $summary = (clone $base)->selectRaw(
            'SUM(CASE WHEN status = "open" THEN 1 ELSE 0 END) as open_count,
            SUM(CASE WHEN status = "applied" THEN 1 ELSE 0 END) as applied_count,
            SUM(CASE WHEN status = "dismissed" THEN 1 ELSE 0 END) as dismissed_count,
            SUM(CASE WHEN status = "open" AND severity IN ("high","critical") THEN 1 ELSE 0 END) as high_open_count'
        )->first();

        $trend = (clone $base)
            ->selectRaw('date as day, SUM(CASE WHEN status = "open" THEN 1 ELSE 0 END) as open_count')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $latestOpen = (clone $base)
            ->where('status', 'open')
            ->latest('date')
            ->latest('id')
            ->limit(5)
            ->get(['id', 'date', 'marketplace', 'sku', 'action_type', 'severity', 'title']);

        return [
            'open' => (int) ($summary->open_count ?? 0),
            'applied' => (int) ($summary->applied_count ?? 0),
            'dismissed' => (int) ($summary->dismissed_count ?? 0),
            'high_open' => (int) ($summary->high_open_count ?? 0),
            'trend' => $trend,
            'latest_open' => $latestOpen,
        ];
    }
}
