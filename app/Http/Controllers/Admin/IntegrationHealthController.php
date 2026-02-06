<?php

namespace App\Http\Controllers\Admin;

use App\Services\IntegrationHealthService;
use App\Support\SupportUser;
use Illuminate\View\View;

class IntegrationHealthController
{
    public function __construct(private readonly IntegrationHealthService $health)
    {
    }

    public function index(): View
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);

        $summary = $this->health->getTenantHealthSummary($user->id);
        $selected = request()->string('marketplace')->toString();
        if ($selected !== '') {
            $summary = array_values(array_filter($summary, function (array $row) use ($selected): bool {
                return (string) ($row['marketplace_code'] ?? '') === $selected;
            }));
        }
        $computedAt = $summary[0]['computed_at'] ?? now();

        return view('admin.integrations.health.index', [
            'healthSummary' => $summary,
            'generatedAt' => $computedAt,
            'selectedMarketplace' => $selected,
        ]);
    }
}
