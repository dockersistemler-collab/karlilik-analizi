<?php

namespace App\Services\Reports;

use App\Models\Marketplace;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OrdersRevenueReportService
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

        $granularity = $this->determineGranularity($filters['date_from'] ?? null, $filters['date_to'] ?? null);
        $driver = $query->getConnection()->getDriverName();
        $periodExpr = $this->buildPeriodExpression($driver, $granularity);

        $rows = (clone $query)
            ->selectRaw($periodExpr . ' as period')
            ->selectRaw('marketplace_id')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as revenue')
            ->selectRaw('COUNT(*) as orders')
            ->groupBy('period', 'marketplace_id')
            ->orderBy('period')
            ->get();

        $table = $this->pivotRows($rows, $marketplaces);
        $chart = $this->buildChartData($table);
        $distribution = $this->buildDistributionData((clone $query), $marketplaces);

        return [
            'marketplaces' => $marketplaces,
            'granularity' => $granularity,
            'table' => $table,
            'chart' => $chart,
            'distribution' => $distribution,
        ];
    }

    private function determineGranularity(?string $dateFrom, ?string $dateTo): string
    {
        if (!$dateFrom || !$dateTo) {
            return 'daily';
        }
$start = Carbon::parse($dateFrom);
        $end = Carbon::parse($dateTo);

        return $start->diffInDays($end) > 31 ? 'monthly' : 'daily';
    }

    private function buildPeriodExpression(string $driver, string $granularity): string
    {
        if ($granularity === 'monthly') {
            return match ($driver) {
                'sqlite' => "strftime('%Y-%m', order_date)",
                'pgsql' => "TO_CHAR(order_date, 'YYYY-MM')",
                'sqlsrv' => "FORMAT(order_date, 'yyyy-MM')",
                default => "DATE_FORMAT(order_date, '%Y-%m')",
            };
        }

        return match ($driver) {
            'sqlite' => "strftime('%Y-%m-%d', order_date)",
            'pgsql' => "TO_CHAR(order_date, 'YYYY-MM-DD')",
            'sqlsrv' => "CONVERT(varchar(10), order_date, 23)",
            default => 'DATE(order_date)',
        };
    }

    private function pivotRows(Collection $rows, Collection $marketplaces): array
    {
        $periods = $rows->pluck('period')->unique()->values();
        $table = [];

        foreach ($periods as $period) {
            $row = [
                'period' => $period,
                'total' => 0,
                'orders_total' => 0,
            ];

            foreach ($marketplaces as $marketplace) {
                $row['mp_' . $marketplace->id] = 0;
                $row['mp_orders_' . $marketplace->id] = 0;
            }

            foreach ($rows->where('period', $period) as $item) {
                $row['mp_' . $item->marketplace_id] = (float) $item->revenue;
                $row['mp_orders_' . $item->marketplace_id] = (int) $item->orders;
                $row['total'] += (float) $item->revenue;
                $row['orders_total'] += (int) $item->orders;
            }
$table[] = $row;
        }

        return $table;
    }

    private function buildChartData(array $table): array
    {
        $labels = [];
        $revenue = [];
        $orders = [];

        foreach ($table as $row) {
            $labels[] = $row['period'];
            $revenue[] = (float) $row['total'];
            $orders[] = (int) $row['orders_total'];
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'orders' => $orders,
        ];
    }

    private function buildDistributionData($query, Collection $marketplaces): array
    {
        $rows = $query
            ->select('marketplace_id')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as revenue')
            ->selectRaw('COUNT(*) as orders')
            ->groupBy('marketplace_id')
            ->orderByDesc('revenue')
            ->get();

        $labels = [];
        $revenue = [];
        $orders = [];

        foreach ($marketplaces as $marketplace) {
            $row = $rows->firstWhere('marketplace_id', $marketplace->id);
            $labels[] = $marketplace->name;
            $revenue[] = (float) ($row->revenue ?? 0);
            $orders[] = (int) ($row->orders ?? 0);
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'orders' => $orders,
        ];
    }
}
