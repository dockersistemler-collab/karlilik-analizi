<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Incident extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id',
        'assigned_to_user_id',
        'marketplace',
        'key',
        'title',
        'status',
        'severity',
        'first_seen_at',
        'last_seen_at',
        'acknowledged_at',
        'resolved_at',
        'meta',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function events()
    {
        return $this->hasMany(IncidentEvent::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function isAckBreached(): bool
    {
        if ($this->resolved_at || $this->status === 'resolved') {
            return false;
        }

        if ($this->acknowledged_at) {
            return false;
        }
$slaMinutes = (int) config('incident_sla.ack_sla_minutes', 30);
        if (!$this->first_seen_at) {
            return false;
        }

        return now()->greaterThan($this->first_seen_at->copy()->addMinutes($slaMinutes));
    }

    public function isResolveBreached(): bool
    {
        if ($this->resolved_at || $this->status === 'resolved') {
            return false;
        }
$slaMinutes = (int) config('incident_sla.resolve_sla_minutes', 240);
        if (!$this->first_seen_at) {
            return false;
        }

        return now()->greaterThan($this->first_seen_at->copy()->addMinutes($slaMinutes));
    }

    public function mttaSeconds(): ?int
    {
        if (!$this->acknowledged_at || !$this->first_seen_at) {
            return null;
        }

        return $this->first_seen_at->diffInSeconds($this->acknowledged_at);
    }

    public function mttrSeconds(): ?int
    {
        if (!$this->resolved_at || !$this->first_seen_at) {
            return null;
        }

        return $this->first_seen_at->diffInSeconds($this->resolved_at);
    }
}
