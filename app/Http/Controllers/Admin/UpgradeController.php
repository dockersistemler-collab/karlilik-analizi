<?php

namespace App\Http\Controllers\Admin;

use App\Services\Features\FeatureGate;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UpgradeController
{
    public function __construct(private readonly FeatureGate $features)
    {
    }

    public function index(Request $request): View
    {
        $feature = (string) $request->query('feature', '');
        $labels = $this->features->featureLabels();
        $descriptions = $this->features->featureDescriptions();

        return view('admin.upgrade', [
            'featureKey' => $feature,
            'featureLabel' => $labels[$feature] ?? 'Bu Ozellik',
            'featureDescription' => $descriptions[$feature] ?? 'Bu ozellik mevcut planinizda bulunmuyor.',
        ]);
    }
}
