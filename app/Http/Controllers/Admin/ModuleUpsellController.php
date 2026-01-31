<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleUpsellController extends Controller
{
    public function show(Request $request, string $code): View
    {
        $module = Module::query()->where('code', $code)->first();

        $marketplace = null;
        if (!$module && str_starts_with($code, 'integration.')) {
            $marketplaceCode = substr($code, strlen('integration.'));
            $marketplace = Marketplace::query()->where('code', $marketplaceCode)->first();
        }

        return view('admin.modules.upsell', compact('code', 'module', 'marketplace'));
    }
}

