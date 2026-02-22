<?php

namespace App\Domains\Settlements\Models;

use App\Domains\Tenancy\Concerns\BelongsToTenant;
use App\Models\MarketplaceAccount;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncJob extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'marketplace_account_id',
        'job_type',
        'status',
        'started_at',
        'finished_at',
        'stats',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'stats' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function account()
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    public function logs()
    {
        return $this->hasMany(SyncLog::class);
    }
}

