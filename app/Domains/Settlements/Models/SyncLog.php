<?php

namespace App\Domains\Settlements\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'sync_job_id',
        'level',
        'message',
        'context',
        'created_at',
    ];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public function syncJob()
    {
        return $this->belongsTo(SyncJob::class);
    }
}

