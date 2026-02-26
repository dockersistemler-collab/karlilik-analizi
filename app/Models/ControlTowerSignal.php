<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ControlTowerSignal extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'date',
        'scope',
        'marketplace',
        'sku',
        'severity',
        'type',
        'title',
        'message',
        'drivers',
        'action_hint',
        'is_resolved',
        'resolved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'drivers' => 'array',
        'action_hint' => 'array',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];
}

