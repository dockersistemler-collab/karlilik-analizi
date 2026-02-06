<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\MarketplaceCredential;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function index()
    {
        $marketplaces = Marketplace::with(['credential' => function ($q) {
    $q->where('user_id', auth()->id());
}])->get();
        return view('admin.marketplaces.index', compact('marketplaces'));
    }

    public function create()
    {
        return view('admin.marketplaces.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:255',
            'code' => 'required|string|unique:marketplaces,code',
            'api_url' => 'nullable|url',
            'is_active' => 'boolean',
        ]);

        $marketplace = Marketplace::create($validated);

        return redirect()->route('portal.marketplaces.index')
            ->with('success', 'Pazaryeri başarıyla oluşturuldu.');
    }

    public function show(Marketplace $marketplace)
    {
        $marketplace->load(['credential' => function ($q) {
        $q->where('user_id', auth()->id());
    },
    'products',
    'orders'
]);
        return view('admin.marketplaces.show', compact('marketplace'));
    }

    public function edit(Marketplace $marketplace)
    {
        $marketplace->load(['credential' => function ($q) {
    $q->where('user_id', auth()->id());
}]);
        return view('admin.marketplaces.edit', compact('marketplace'));
    }

    public function update(Request $request, Marketplace $marketplace)
    {
        $validated = $request->validate(['name' => 'required|string|max:255',
            'code' => 'required|string|unique:marketplaces,code,' . $marketplace->id,
            'api_url' => 'nullable|url',
            'is_active' => 'boolean',
        ]);

        $marketplace->update($validated);

        // Credential bilgilerini güncelle (kullanıcı bazlı tek kayıt)
if ($request->filled('api_key') || $request->filled('api_secret') || $request->filled('supplier_id')) {

    $credentialData = $request->only(['api_key',
        'api_secret',
        'supplier_id',
        'store_id',
    ]);

    // checkbox gelmezse false sayalım
    $credentialData['is_active'] = $request->boolean('is_active');

    MarketplaceCredential::updateOrCreate(
        [
            'user_id' => auth()->id(),
            'marketplace_id' => $marketplace->id,
        ],
        $credentialData
    );
}

        return redirect()->route('portal.marketplaces.index')
            ->with('success', 'Pazaryeri başarıyla güncellendi.');
    }

    public function destroy(Marketplace $marketplace)
    {
        $marketplace->delete();

        return redirect()->route('portal.marketplaces.index')
            ->with('success', 'Pazaryeri başarıyla silindi.');
    }
}


