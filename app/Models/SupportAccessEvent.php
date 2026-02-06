<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportAccessEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_access_log_id',
        'actor_user_id',
        'target_user_id',
        'type',
        'method',
        'route_name',
        'url',
        'ip',
        'user_agent',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
