<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DetectMarketplaceShocksJob;
use App\Jobs\RunActionEngineCalibrationJob;
use App\Jobs\RunActionEngineDailyJob;
use App\Models\ActionEngineCalibration;
use App\Models\ActionEngineRun;
use App\Models\ActionRecommendation;
use App\Models\Marketplace;
use App\Models\MarketplaceCampaign;
use App\Models\MarketplaceExternalShock;
use App\Services\ActionEngine\CampaignCalendarApplier;
use App\Services\ActionEngine\CampaignCsvImporter;
use App\Services\ActionEngine\ImpactSimulator;
use App\Support\SupportUser;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActionEngineController extends Controller
{
    public function index(Request $request): View
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);
        $tenantId = (int) ($owner->tenant_id ?: $owner->id);

        $query = ActionRecommendation::query()
            ->where('tenant_id', $tenantId)
            ->when($request->filled('status'), fn ($q) => $q->where('status', (string) $request->input('status')))
            ->when($request->filled('marketplace'), fn ($q) => $q->where('marketplace', strtolower((string) $request->input('marketplace'))))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('date', '>=', (string) $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('date', '<=', (string) $request->input('date_to')))
            ->latest('date')
            ->latest('id');

        $recommendations = $query->paginate(20)->withQueryString();
        $marketplaces = Marketplace::query()->where('is_active', true)->orderBy('name')->get();

        $overview = [
            'open' => ActionRecommendation::query()->where('tenant_id', $tenantId)->where('status', 'open')->count(),
            'applied' => ActionRecommendation::query()->where('tenant_id', $tenantId)->where('status', 'applied')->count(),
            'dismissed' => ActionRecommendation::query()->where('tenant_id', $tenantId)->where('status', 'dismissed')->count(),
            'critical_open' => ActionRecommendation::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'open')
                ->whereIn('severity', ['high', 'critical'])
                ->count(),
        ];

        $latestRuns = ActionEngineRun::query()
            ->where('tenant_id', $tenantId)
            ->latest('run_date')
            ->limit(10)
            ->get();

        return view('admin.action-engine.index', compact('recommendations', 'marketplaces', 'overview', 'latestRuns'));
    }

    public function show(ActionRecommendation $recommendation): View
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);
        if (!$owner->isSuperAdmin() && (int) $recommendation->tenant_id !== (int) ($owner->tenant_id ?: $owner->id)) {
            abort(403);
        }

        $recommendation->load('impact');

        return view('admin.action-engine.show', compact('recommendation'));
    }

    public function apply(ActionRecommendation $recommendation): RedirectResponse
    {
        return $this->decide($recommendation, 'applied');
    }

    public function dismiss(ActionRecommendation $recommendation): RedirectResponse
    {
        return $this->decide($recommendation, 'dismissed');
    }

    public function run(Request $request): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);

        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $from = CarbonImmutable::parse($validated['date_from']);
        $to = CarbonImmutable::parse($validated['date_to']);

        for ($cursor = $from; $cursor->lessThanOrEqualTo($to); $cursor = $cursor->addDay()) {
            RunActionEngineDailyJob::dispatch((int) $owner->id, $cursor->toDateString());
        }

        return back()->with('success', 'Action Engine manual run kuyruga alindi.');
    }

    public function refreshImpact(ActionRecommendation $recommendation, ImpactSimulator $simulator): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);
        if (!$owner->isSuperAdmin() && (int) $recommendation->tenant_id !== (int) ($owner->tenant_id ?: $owner->id)) {
            abort(403);
        }

        $simulator->simulateAndStore($recommendation);

        return back()->with('success', 'Etki simulasyonu yenilendi.');
    }

    public function calibration(Request $request): View
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);
        $tenantId = (int) ($owner->tenant_id ?: $owner->id);

        $calibrations = ActionEngineCalibration::query()
            ->where('tenant_id', $tenantId)
            ->when($request->filled('marketplace'), fn ($q) => $q->where('marketplace', strtolower((string) $request->input('marketplace'))))
            ->orderByDesc('calculated_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.action-engine.calibration', compact('calibrations'));
    }

    public function shocks(Request $request): View
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);
        $tenantId = (int) ($owner->tenant_id ?: $owner->id);

        $shocks = MarketplaceExternalShock::query()
            ->where('tenant_id', $tenantId)
            ->when($request->filled('marketplace'), fn ($q) => $q->where('marketplace', strtolower((string) $request->input('marketplace'))))
            ->when($request->filled('shock_type'), fn ($q) => $q->where('shock_type', (string) $request->input('shock_type')))
            ->latest('date')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();
        $marketplaces = Marketplace::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.action-engine.shocks', compact('shocks', 'marketplaces'));
    }

    public function campaigns(): View
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);
        $tenantId = (int) ($owner->tenant_id ?: $owner->id);
        $campaigns = MarketplaceCampaign::query()
            ->where('tenant_id', $tenantId)
            ->withCount('items')
            ->latest('start_date')
            ->paginate(30);
        $marketplaces = Marketplace::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.action-engine.campaigns', compact('campaigns', 'marketplaces'));
    }

    public function campaignsImport(Request $request, CampaignCsvImporter $importer): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);

        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $tenantId = (int) ($owner->tenant_id ?: $owner->id);
        $result = $importer->import($tenantId, (int) $owner->id, $validated['file']->getRealPath());

        return back()->with('success', "Campaign import tamamlandi. campaigns={$result['campaigns']} items={$result['items']}");
    }

    public function campaignsApply(Request $request, CampaignCalendarApplier $applier): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);
        $validated = $request->validate([
            'campaign_id' => 'nullable|integer',
        ]);

        $tenantId = (int) ($owner->tenant_id ?: $owner->id);
        $result = $applier->applyForTenant($tenantId, (int) $owner->id, isset($validated['campaign_id']) ? (int) $validated['campaign_id'] : null);

        return back()->with('success', "Campaign apply tamamlandi. campaigns={$result['campaigns']} updated_rows={$result['updated_rows']}");
    }

    public function runShockDetect(Request $request): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);
        $date = (string) $request->input('date', now()->subDay()->toDateString());
        DetectMarketplaceShocksJob::dispatch((int) $owner->id, $date, 45);

        return back()->with('success', 'Shock detection kuyruga alindi.');
    }

    public function runCalibration(Request $request): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);
        $date = (string) $request->input('date', now()->subDay()->toDateString());
        RunActionEngineCalibrationJob::dispatch((int) $owner->id, $date, 45);

        return back()->with('success', 'Calibration kuyruga alindi.');
    }

    private function decide(ActionRecommendation $recommendation, string $status): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);
        if (!$owner->isSuperAdmin() && (int) $recommendation->tenant_id !== (int) ($owner->tenant_id ?: $owner->id)) {
            abort(403);
        }

        $recommendation->update([
            'status' => $status,
            'decided_at' => now(),
            'decided_by' => $owner->id,
        ]);

        return back()->with('success', 'Oneri durumu guncellendi.');
    }
}

