<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\CalculateMarketplaceRiskJob;
use App\Models\Marketplace;
use App\Models\MarketplaceKpiSnapshot;
use App\Models\MarketplaceRiskProfile;
use App\Models\MarketplaceRiskScore;
use App\Services\MarketplaceRisk\ProfileResolver;
use App\Support\SupportUser;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceRiskController extends Controller
{
    public function index(Request $request): View
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);

        $tenantId = (int) ($owner->tenant_id ?: $owner->id);
        $marketplace = strtolower((string) $request->string('marketplace', ''));
        $date = $request->filled('date')
            ? CarbonImmutable::parse((string) $request->input('date'))
            : CarbonImmutable::yesterday();

        $scores = MarketplaceRiskScore::query()
            ->where('tenant_id', $tenantId)
            ->when($marketplace !== '', fn ($q) => $q->where('marketplace', $marketplace))
            ->orderByDesc('date')
            ->paginate(20)
            ->withQueryString();

        $kpis = MarketplaceKpiSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->when($marketplace !== '', fn ($q) => $q->where('marketplace', $marketplace))
            ->orderByDesc('date')
            ->paginate(20, ['*'], 'kpi_page')
            ->withQueryString();

        $profiles = MarketplaceRiskProfile::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('is_default')
            ->latest('id')
            ->get();

        $alerts = MarketplaceRiskScore::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['warning', 'critical'])
            ->orderByDesc('date')
            ->limit(20)
            ->get();

        $marketplaces = Marketplace::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.marketplace-risk.index', compact(
            'scores',
            'kpis',
            'profiles',
            'alerts',
            'marketplaces',
            'date'
        ));
    }

    public function storeKpi(Request $request): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);

        $validated = $request->validate([
            'marketplace' => 'required|string|max:50',
            'date' => 'required|date',
            'late_shipment_rate' => 'nullable|numeric|min:0|max:100',
            'cancellation_rate' => 'nullable|numeric|min:0|max:100',
            'return_rate' => 'nullable|numeric|min:0|max:100',
            'performance_score' => 'nullable|numeric|min:0|max:100',
            'rating_score' => 'nullable|numeric|min:0|max:5',
            'odr' => 'nullable|numeric|min:0|max:100',
            'valid_tracking_rate' => 'nullable|numeric|min:0|max:100',
            'source' => 'nullable|string|max:40',
        ]);

        $tenantId = (int) ($owner->tenant_id ?: $owner->id);
        MarketplaceKpiSnapshot::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'marketplace' => strtolower($validated['marketplace']),
                'date' => $validated['date'],
            ],
            [
                'user_id' => $owner->id,
                'late_shipment_rate' => $validated['late_shipment_rate'] ?? null,
                'cancellation_rate' => $validated['cancellation_rate'] ?? null,
                'return_rate' => $validated['return_rate'] ?? null,
                'performance_score' => $validated['performance_score'] ?? null,
                'rating_score' => $validated['rating_score'] ?? null,
                'odr' => $validated['odr'] ?? null,
                'valid_tracking_rate' => $validated['valid_tracking_rate'] ?? null,
                'source' => $validated['source'] ?? 'manual',
                'meta' => ['input' => 'admin_form'],
            ]
        );

        CalculateMarketplaceRiskJob::dispatch($owner->id, (string) $validated['date']);

        return back()->with('success', 'KPI kaydedildi ve risk hesaplama kuyruğa alındı.');
    }

    public function updateKpi(Request $request, MarketplaceKpiSnapshot $kpi): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);
        if (!$owner->isSuperAdmin() && (int) $kpi->user_id !== (int) $owner->id) {
            abort(403);
        }

        $validated = $request->validate([
            'late_shipment_rate' => 'nullable|numeric|min:0|max:100',
            'cancellation_rate' => 'nullable|numeric|min:0|max:100',
            'return_rate' => 'nullable|numeric|min:0|max:100',
            'performance_score' => 'nullable|numeric|min:0|max:100',
            'rating_score' => 'nullable|numeric|min:0|max:5',
            'odr' => 'nullable|numeric|min:0|max:100',
            'valid_tracking_rate' => 'nullable|numeric|min:0|max:100',
            'source' => 'nullable|string|max:40',
        ]);

        $kpi->update($validated);
        CalculateMarketplaceRiskJob::dispatch((int) $kpi->user_id, (string) $kpi->date->toDateString());

        return back()->with('success', 'KPI güncellendi.');
    }

    public function importCsv(Request $request): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);

        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $path = $validated['file']->getRealPath();
        $handle = fopen($path, 'rb');
        if (!$handle) {
            return back()->with('error', 'CSV dosyası açılamadı.');
        }

        $header = fgetcsv($handle);
        if (!is_array($header)) {
            fclose($handle);
            return back()->with('error', 'CSV başlık satırı okunamadı.');
        }

        $map = [];
        foreach ($header as $i => $name) {
            $map[strtolower(trim((string) $name))] = $i;
        }

        $required = ['date', 'marketplace'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $map)) {
                fclose($handle);
                return back()->with('error', "CSV kolon eksik: {$key}");
            }
        }

        $tenantId = (int) ($owner->tenant_id ?: $owner->id);
        $affectedDates = [];
        while (($row = fgetcsv($handle)) !== false) {
            $date = trim((string) ($row[$map['date']] ?? ''));
            $marketplace = strtolower(trim((string) ($row[$map['marketplace']] ?? '')));
            if ($date === '' || $marketplace === '') {
                continue;
            }

            MarketplaceKpiSnapshot::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'marketplace' => $marketplace,
                    'date' => $date,
                ],
                [
                    'user_id' => $owner->id,
                    'late_shipment_rate' => $this->csvValue($row, $map, 'late_shipment_rate'),
                    'cancellation_rate' => $this->csvValue($row, $map, 'cancellation_rate'),
                    'return_rate' => $this->csvValue($row, $map, 'return_rate'),
                    'performance_score' => $this->csvValue($row, $map, 'performance_score'),
                    'rating_score' => $this->csvValue($row, $map, 'rating_score'),
                    'odr' => $this->csvValue($row, $map, 'odr'),
                    'valid_tracking_rate' => $this->csvValue($row, $map, 'valid_tracking_rate'),
                    'source' => 'csv',
                    'meta' => ['input' => 'csv_import'],
                ]
            );

            $affectedDates[$date] = true;
        }
        fclose($handle);

        foreach (array_keys($affectedDates) as $date) {
            CalculateMarketplaceRiskJob::dispatch($owner->id, (string) $date);
        }

        return back()->with('success', 'CSV import tamamlandı.');
    }

    public function storeProfile(Request $request, ProfileResolver $resolver): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);

        $validated = $request->validate([
            'marketplace' => 'required|string|max:50',
            'name' => 'required|string|max:120',
            'weights' => 'required|string',
            'thresholds' => 'required|string',
            'metric_thresholds' => 'required|string',
            'is_default' => 'nullable|boolean',
        ]);

        $tenantId = (int) ($owner->tenant_id ?: $owner->id);
        $marketplace = strtolower($validated['marketplace']);
        $isDefault = (bool) ($validated['is_default'] ?? false);

        $weights = json_decode($validated['weights'], true);
        $thresholds = json_decode($validated['thresholds'], true);
        $metricThresholds = json_decode($validated['metric_thresholds'], true);

        if (!is_array($weights) || !is_array($thresholds) || !is_array($metricThresholds)) {
            return back()->with('error', 'Profil JSON alanları geçersiz.');
        }

        if ($isDefault) {
            MarketplaceRiskProfile::query()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $owner->id)
                ->where('marketplace', $marketplace)
                ->update(['is_default' => false]);
        }

        MarketplaceRiskProfile::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $owner->id,
            'marketplace' => $marketplace,
            'name' => $validated['name'],
            'weights' => $weights,
            'thresholds' => $thresholds,
            'metric_thresholds' => $metricThresholds,
            'is_default' => $isDefault,
        ]);

        $resolver->resolveDefault($tenantId, $owner->id, $marketplace);

        return back()->with('success', 'Risk profili kaydedildi.');
    }

    public function updateProfile(Request $request, MarketplaceRiskProfile $profile): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);
        if (!$owner->isSuperAdmin() && (int) $profile->user_id !== (int) $owner->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'weights' => 'required|string',
            'thresholds' => 'required|string',
            'metric_thresholds' => 'required|string',
            'is_default' => 'nullable|boolean',
        ]);

        $weights = json_decode($validated['weights'], true);
        $thresholds = json_decode($validated['thresholds'], true);
        $metricThresholds = json_decode($validated['metric_thresholds'], true);

        if (!is_array($weights) || !is_array($thresholds) || !is_array($metricThresholds)) {
            return back()->with('error', 'Profil JSON alanları geçersiz.');
        }

        $isDefault = (bool) ($validated['is_default'] ?? false);
        if ($isDefault) {
            MarketplaceRiskProfile::query()
                ->where('tenant_id', $profile->tenant_id)
                ->where('user_id', $profile->user_id)
                ->where('marketplace', $profile->marketplace)
                ->where('id', '!=', $profile->id)
                ->update(['is_default' => false]);
        }

        $profile->update([
            'name' => $validated['name'],
            'weights' => $weights,
            'thresholds' => $thresholds,
            'metric_thresholds' => $metricThresholds,
            'is_default' => $isDefault,
        ]);

        return back()->with('success', 'Risk profili güncellendi.');
    }

    public function destroyProfile(MarketplaceRiskProfile $profile): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);
        if (!$owner->isSuperAdmin() && (int) $profile->user_id !== (int) $owner->id) {
            abort(403);
        }

        $profile->delete();

        return back()->with('success', 'Risk profili silindi.');
    }

    private function csvValue(array $row, array $map, string $key): ?float
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
}

