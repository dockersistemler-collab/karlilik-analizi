<?php

namespace App\Domains\Settlements\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReconciliationRule extends Model
{
    use HasFactory;

    protected $fillable = [
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
    ];
}

