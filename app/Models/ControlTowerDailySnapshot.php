<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ControlTowerDailySnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'date',
        'payload',
    ];

    protected $casts = [
        'date' => 'date',
        'payload' => 'array',
    ];
}

