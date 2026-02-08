<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportFilters
{
    public static function fromRequest(Request $request, bool $defaultThisMonth = false): array
    {
        $marketplaceId = $request->input('marketplace_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $quickRange = $request->input('quick_range');

        if ($quickRange) {
            [$dateFrom, $dateTo] = self::resolveQuickRange($quickRange);
        }

        if (!$dateFrom && !$dateTo && $defaultThisMonth) {
            $dateFrom = Carbon::today()->startOfMonth()->toDateString();
            $dateTo = Carbon::today()->endOfMonth()->toDateString();
        }

        return [
            'marketplace_id' => $marketplaceId ? (int) $marketplaceId : null,
            'date_from' => $dateFrom ?: null,
            'date_to' => $dateTo ?: null,
            'quick_range' => $quickRange ?: null,
        ];
    }

    public static function applyDateRange($query, string $column, ?string $dateFrom, ?string $dateTo): void
    {
        if ($dateFrom) {
            $query->whereDate($column, '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate($column, '<=', $dateTo);
        }
    }

    public static function resolveQuickRange(string $quickRange): array
    {
        $today = Carbon::today();

        return match ($quickRange) {
            'today' => [$today->toDateString(), $today->toDateString()],
            'this_week' => [$today->copy()->startOfWeek()->toDateString(), $today->copy()->endOfWeek()->toDateString()],
            'this_month' => [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
            'last_month' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            'last_3_months' => [
                $today->copy()->subMonthsNoOverflow(2)->startOfMonth()->toDateString(),
                $today->copy()->toDateString(),
            ],
            'last_1_year' => [
                $today->copy()->subYearNoOverflow()->toDateString(),
                $today->copy()->toDateString(),
            ],
            'last_7_days' => [$today->copy()->subDays(6)->toDateString(), $today->toDateString()],
            'last_30_days' => [$today->copy()->subDays(29)->toDateString(), $today->toDateString()],
            default => [null, null],
        };
    }
}
