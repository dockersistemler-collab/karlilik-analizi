<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'token_id',
        'ip',
        'user_agent',
        'method',
        'path',
        'status_code',
        'duration_ms',
        'request_id',
        'meta',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

