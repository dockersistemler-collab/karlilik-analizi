<?php

namespace App\Domains\Settlements\Models;

use App\Domains\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LossPattern extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'marketplace',
        'payout_id',
        'run_hash',
        'run_version',
        'pattern_key',
        'code',
        'type',
        'occurrences',
        'finding_code',
        'severity',
        'occurrence_count',
        'total_amount',
        'avg_confidence',
        'first_seen_at',
        'last_seen_at',
        'sample_finding_id',
        'meta',
        'examples_json',
    ];

    protected $casts = [
        'occurrence_count' => 'integer',
        'occurrences' => 'integer',
        'total_amount' => 'decimal:2',
        'avg_confidence' => 'decimal:2',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'sample_finding_id' => 'integer',
        'meta' => 'array',
        'examples_json' => 'array',
    ];

    public function payout()
    {
        return $this->belongsTo(Payout::class);
    }
}
