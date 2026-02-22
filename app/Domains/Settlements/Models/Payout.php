<?php

namespace App\Domains\Settlements\Models;

use App\Domains\Tenancy\Concerns\BelongsToTenant;
use App\Models\MarketplaceAccount;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'marketplace_integration_id',
        'marketplace_account_id',
        'payout_reference',
        'period_start',
        'period_end',
        'expected_date',
        'expected_amount',
        'paid_amount',
        'paid_date',
        'currency',
        'status',
        'totals',
        'raw_payload',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'expected_date' => 'date',
        'paid_date' => 'date',
        'expected_amount' => 'decimal:4',
        'paid_amount' => 'decimal:4',
        'totals' => 'array',
        'raw_payload' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function integration()
    {
        return $this->belongsTo(MarketplaceIntegration::class, 'marketplace_integration_id');
    }

    public function account()
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    public function transactions()
    {
        return $this->hasMany(PayoutTransaction::class);
    }

    public function reconciliation()
    {
        return $this->hasOne(Reconciliation::class);
    }

    public function disputes()
    {
        return $this->hasMany(Dispute::class);
    }
}

