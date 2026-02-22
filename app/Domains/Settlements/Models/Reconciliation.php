<?php

namespace App\Domains\Settlements\Models;

use App\Domains\Tenancy\Concerns\BelongsToTenant;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reconciliation extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'payout_id',
        'matched_payment_reference',
        'matched_amount',
        'matched_date',
        'match_method',
        'tolerance_used',
        'notes',
    ];

    protected $casts = [
        'matched_date' => 'date',
        'matched_amount' => 'decimal:4',
        'tolerance_used' => 'decimal:4',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payout()
    {
        return $this->belongsTo(Payout::class);
    }
}

