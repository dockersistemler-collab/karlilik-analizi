<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Marketplaces\Jobs\SyncMarketplaceAccountJob;
use App\Domains\Settlements\Actions\BuildExpectedPayoutsAction;
use App\Http\Controllers\Api\V1\Concerns\ResolvesTenant;
use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    use ResolvesTenant;

    public function sync(Request $request, int $id, BuildExpectedPayoutsAction $buildExpectedPayoutsAction)
    {
        $tenantId = $this->currentTenantId();
        $account = MarketplaceAccount::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'sync_mode' => ['nullable', 'in:queued,sync'],
        ]);

        if (($validated['sync_mode'] ?? 'queued') === 'sync') {
            (new SyncMarketplaceAccountJob($account->id, $validated['from'], $validated['to']))
                ->handle(
                    app(\App\Domains\Marketplaces\Services\MarketplaceConnectorRegistry::class),
                    app(\App\Domains\Marketplaces\Mappers\MarketplacePayloadMapper::class),
                    app(\App\Domains\Marketplaces\Services\SyncLogService::class),
                );
        } else {
            SyncMarketplaceAccountJob::dispatch($account->id, $validated['from'], $validated['to']);
        }

        $payout = $buildExpectedPayoutsAction->execute($account->id, $validated['from'], $validated['to']);

        return ApiResponse::success([
            'queued' => ($validated['sync_mode'] ?? 'queued') !== 'sync',
            'payout_id' => $payout->id,
        ]);
    }
}

