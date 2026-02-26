<?php

namespace App\Domains\Settlements\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReconciliationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'scope',
        'scope_type',
        'scope_key',
        'valid_from',
        'valid_to',
        'marketplace',
        'rule_type',
        'key',
        'value',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'value' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'tenant_id' => 'integer',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];
}
