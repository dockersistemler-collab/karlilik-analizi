<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SupportAccessLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'super_admin_id',
        'actor_user_id',
        'actor_role',
        'source_type',
        'source_id',
        'target_user_id',
        'started_at',
        'ended_at',
        'expires_at',
        'ip',
        'user_agent',
        'reason',
        'scope',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function superAdmin()
    {
        return $this->belongsTo(User::class, 'super_admin_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('ended_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query
            ->whereNull('ended_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }
}
