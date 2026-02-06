<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\Cargo\ShipmentTrackingService;
use App\Support\SupportUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    private function ensureOwner(Shipment $shipment): void
    {
        $user = SupportUser::currentUser();
        if ($user && !$user->isSuperAdmin() && $shipment->user_id !== $user->id) {
            abort(403);
        }
    }

    public function index(Request $request): View
    {
        $user = SupportUser::currentUser();
        $query = Shipment::query()
            ->with(['order.marketplace'])
            ->latest();

        if ($user && !$user->isSuperAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
$shipments = $query->paginate(20)->withQueryString();

        return view('admin.shipments.index', compact('shipments'));
    }

    public function show(Shipment $shipment): View
    {
        $this->ensureOwner($shipment);

        $shipment->loadMissing(['order.marketplace']);
        $events = $shipment->events()
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get();

        return view('admin.shipments.show', compact('shipment', 'events'));
    }

    public function poll(Shipment $shipment, ShipmentTrackingService $service): RedirectResponse
    {
        $this->ensureOwner($shipment);

        $service->poll($shipment);

        return back()->with('success', 'Kargo takibi güncellendi.');
    }
}
