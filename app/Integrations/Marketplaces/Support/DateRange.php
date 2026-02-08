<?php

namespace App\Integrations\Marketplaces\Support;

use Carbon\CarbonImmutable;

class DateRange
{
    public CarbonImmutable $from;
    public CarbonImmutable $to;

    public function __construct(CarbonImmutable $from, CarbonImmutable $to)
    {
        if ($to->lessThan($from)) {
            throw new \InvalidArgumentException('DateRange: "to" must be >= "from".');
        }

        $this->from = $from;
        $this->to = $to;
    }

    public function toArray(): array
    {
        return [
            'from' => $this->from->toDateTimeString(),
            'to' => $this->to->toDateTimeString(),
        ];
    }
}
