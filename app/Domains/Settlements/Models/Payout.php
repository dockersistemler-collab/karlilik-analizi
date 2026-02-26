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
        'marketplace',
        'marketplace_integration_id',
        'marketplace_account_id',
        'account_id',
        'payout_reference',
        'payout_no',
        'period_start',
        'period_end',
        'expected_date',
        'expected_amount',
        'paid_amount',
        'paid_date',
        'paid_at',
        'imported_at',
        'file_name',
        'file_hash',
        'currency',
        'status',
        'regression_flag',
        'regression_note',
        'regression_checked_at',
        'totals',
        'raw_payload',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'expected_date' => 'date',
        'paid_date' => 'date',
        'paid_at' => 'datetime',
        'imported_at' => 'datetime',
        'regression_flag' => 'boolean',
        'regression_checked_at' => 'datetime',
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

    public function rows()
    {
        return $this->hasMany(PayoutRow::class);
    }

    public function reconciliation()
    {
        return $this->hasOne(Reconciliation::class)->latestOfMany('reconciled_at');
    }

    public function reconciliations()
    {
        return $this->hasMany(Reconciliation::class);
    }

    public function disputes()
    {
        return $this->hasMany(Dispute::class);
    }

    public function lossPatterns()
    {
        return $this->hasMany(LossPattern::class);
    }
}
