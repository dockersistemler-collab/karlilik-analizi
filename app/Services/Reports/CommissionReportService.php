<?php

namespace App\Services\Reports;

use App\Models\Marketplace;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommissionReportService
{
    public function get(User $user, array $filters): array
    {
        $marketplaces = Marketplace::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $query = Order::query()->where('user_id', $user->id);

        if (!empty($filters['marketplace_id'])) {
            $query->where('marketplace_id', $filters['marketplace_id']);
        }

        ReportFilters::applyDateRange($query, 'order_date', $filters['date_from'] ?? null, $filters['date_to'] ?? null);

        $rows = (clone $query)
            ->select('marketplace_id')
            ->selectRaw('COALESCE(SUM(commission_amount), 0) as commission_total')
            ->groupBy('marketplace_id')
            ->get();

        $cards = $marketplaces->map(function ($marketplace) use ($rows) {
            $row = $rows->firstWhere('marketplace_id', $marketplace->id);
            return [
                'name' => $marketplace->name,
                'total' => (float) ($row->commission_total ?? 0),
            ];
        })->values()->all();

        $grandTotal = array_sum(array_column($cards, 'total'));

        return [
            'cards' => $cards,
            'total' => $grandTotal,
        ];
    }
}
