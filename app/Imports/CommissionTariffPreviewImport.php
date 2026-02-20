<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;

class CommissionTariffPreviewImport implements ToArray, WithHeadingRow, WithLimit
{
    public function limit(): int
    {
        return 20;
    }

    public function array(array $array): array
    {
        return $array;
    }
}
