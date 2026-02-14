<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryUserController extends Controller
{
    public function index(Request $request): View
    {
        if (!auth('subuser')->check()) {
            abort(404);
        }

        $tenantId = (int) $request->user()->id;
        $products = Product::query()
            ->where('user_id', $tenantId)
            ->orderBy('name')
            ->paginate(25);

        return view('admin.inventory.user.products', [
            'products' => $products,
        ]);
    }
}
