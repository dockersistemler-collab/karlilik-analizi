<?php

namespace App\Services\EInvoices;

use App\Models\EInvoiceSequence;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class EInvoiceNumberingService
{
    public function nextNumber(User $user, Carbon $date, string $prefix = 'EA'): string
    {
        $year = (int) $date->format('Y');

        return DB::transaction(function () use ($user, $prefix, $year) {
            $sequence = EInvoiceSequence::query()
                ->where('user_id', $user->id)
                ->where('year', $year)
                ->where('prefix', $prefix)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                try {
                    $sequence = EInvoiceSequence::create([
                        'user_id' => $user->id,
                        'year' => $year,
                        'prefix' => $prefix,
                        'last_number' => 0,
                    ]);
                } catch (QueryException) {
                    $sequence = EInvoiceSequence::query()
                        ->where('user_id', $user->id)
                        ->where('year', $year)
                        ->where('prefix', $prefix)
                        ->lockForUpdate()
                        ->first();
                }
$sequence = $sequence
                    ? EInvoiceSequence::query()->whereKey($sequence->id)->lockForUpdate()->first()
                    : null;
            }

            if (!$sequence) {
                throw new \RuntimeException('EInvoice sequence could not be created.');
            }
$sequence->last_number = (int) $sequence->last_number + 1;
            $sequence->save();

            return sprintf('%s%d-%06d', $prefix, $year, (int) $sequence->last_number);
        });
    }
}
