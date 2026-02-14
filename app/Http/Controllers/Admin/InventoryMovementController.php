<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryMovementController extends Controller
{
    public function index(Request $request): View
    {
        if (auth('subuser')->check()) {
            abort(404);
        }

        $tenantId = (int) $request->user()->id;
        $movements = StockMovement::query()
            ->with('product')
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->paginate(50);

        return view('admin.inventory.movements.index', [
            'movements' => $movements,
        ]);
    }
}
