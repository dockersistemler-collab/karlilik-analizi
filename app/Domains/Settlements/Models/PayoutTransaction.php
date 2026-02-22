<?php

namespace App\Domains\Settlements\Models;

use App\Domains\Tenancy\Concerns\BelongsToTenant;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayoutTransaction extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'payout_id',
        'type',
        'reference_id',
        'amount',
        'vat_amount',
        'meta',
        'raw_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'vat_amount' => 'decimal:4',
        'meta' => 'array',
        'raw_payload' => 'array',
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

