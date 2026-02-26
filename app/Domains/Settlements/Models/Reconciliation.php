<?php

namespace App\Domains\Settlements\Models;

use App\Domains\Tenancy\Concerns\BelongsToTenant;
use App\Models\Order;
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
        'order_id',
        'match_key',
        'expected_total_net',
        'actual_total_net',
        'diff_total_net',
        'diff_breakdown_json',
        'loss_findings_json',
        'findings_summary_json',
        'run_hash',
        'run_version',
        'status',
        'reconciled_at',
        'matched_payment_reference',
        'matched_amount',
        'matched_date',
        'match_method',
        'tolerance_used',
        'notes',
    ];

    protected $casts = [
        'matched_date' => 'date',
        'run_version' => 'integer',
        'matched_amount' => 'decimal:4',
        'tolerance_used' => 'decimal:4',
        'expected_total_net' => 'decimal:2',
        'actual_total_net' => 'decimal:2',
        'diff_total_net' => 'decimal:2',
        'diff_breakdown_json' => 'array',
        'loss_findings_json' => 'array',
        'findings_summary_json' => 'array',
        'reconciled_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payout()
    {
        return $this->belongsTo(Payout::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function findings()
    {
        return $this->hasMany(LossFinding::class);
    }
}
