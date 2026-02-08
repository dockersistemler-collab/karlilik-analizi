<?php

namespace App\Integrations\Marketplaces\Support;

use Carbon\CarbonImmutable;

class DateRangeFactory
{
    public function fromString(string $range): DateRange
    {
        $range = trim($range);

        if (str_contains($range, '..')) {
            [$from, $to] = array_map('trim', explode('..', $range, 2));
            return new DateRange(
                CarbonImmutable::parse($from),
                CarbonImmutable::parse($to)
            );
        }

        $now = CarbonImmutable::now();

        return match ($range) {
            'today' => new DateRange($now->startOfDay(), $now->endOfDay()),
            'yesterday' => new DateRange($now->subDay()->startOfDay(), $now->subDay()->endOfDay()),
            'last1day' => new DateRange($now->subDay()->startOfDay(), $now->endOfDay()),
            'last7days' => new DateRange($now->subDays(7)->startOfDay(), $now->endOfDay()),
            'last30days' => new DateRange($now->subDays(30)->startOfDay(), $now->endOfDay()),
            'month' => new DateRange($now->startOfMonth(), $now->endOfMonth()),
            'year' => new DateRange($now->startOfYear(), $now->endOfYear()),
            default => new DateRange($now->subDays(30)->startOfDay(), $now->endOfDay()),
        };
    }
}
