<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActionEngineRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'run_date',
        'stats',
    ];

    protected $casts = [
        'run_date' => 'date',
        'stats' => 'array',
    ];
}

