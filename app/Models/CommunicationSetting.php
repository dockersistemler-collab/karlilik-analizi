<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunicationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ai_enabled',
        'notification_email',
        'cron_interval_minutes',
        'priority_weights',
    ];

    protected function casts(): array
    {
        return [
            'ai_enabled' => 'boolean',
            'cron_interval_minutes' => 'integer',
            'priority_weights' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function forUser(?User $user): self
    {
        if ($user) {
            $userScoped = static::query()->where('user_id', $user->id)->first();
            if ($userScoped) {
                return $userScoped;
            }
        }

        return static::query()->whereNull('user_id')->firstOrFail();
    }
}

