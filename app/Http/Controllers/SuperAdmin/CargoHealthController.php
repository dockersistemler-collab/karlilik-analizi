<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CargoHealthController extends Controller
{
    public function index(): View
    {
        $since = Carbon::now()->subDays(7);

        $unmappedCount = Shipment::query()
            ->where('status', 'unmapped_carrier')
            ->where('created_at', '>=', $since)
            ->count();

        $providerMissingCount = Shipment::query()
            ->where('status', 'provider_not_installed')
            ->where('created_at', '>=', $since)
            ->count();

        $topCarriers = Shipment::query()
            ->selectRaw('carrier_name_raw, COUNT(*) as total')
            ->whereNotNull('carrier_name_raw')
            ->where('created_at', '>=', $since)
            ->groupBy('carrier_name_raw')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return view('super-admin.cargo.health', compact('unmappedCount', 'providerMissingCount', 'topCarriers'));
    }
}
