<?php

namespace App\Domains\Settlements\Models;

use App\Domains\Tenancy\Concerns\BelongsToTenant;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dispute extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'payout_id',
        'dispute_type',
        'expected_amount',
        'actual_amount',
        'diff_amount',
        'status',
        'assigned_user_id',
        'evidence',
        'notes',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:4',
        'actual_amount' => 'decimal:4',
        'diff_amount' => 'decimal:4',
        'evidence' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payout()
    {
        return $this->belongsTo(Payout::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}

