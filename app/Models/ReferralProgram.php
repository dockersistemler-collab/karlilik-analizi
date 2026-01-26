<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ReferralProgram extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'referrer_reward_type',
        'referrer_reward_value',
        'referred_reward_type',
        'referred_reward_value',
        'max_uses_per_referrer_per_year',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'referrer_reward_value' => 'decimal:2',
        'referred_reward_value' => 'decimal:2',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function rewardLabel(string $type, ?string $value): string
    {
        if ($value === null) {
            return '-';
        }

        return match ($type) {
            'percent' => '%'.rtrim(rtrim($value, '0'), '.').' indirim',
            'duration' => rtrim(rtrim($value, '0'), '.').' ay kullanım',
            default => $value,
        };
    }

    public function referrerRewardLabel(): string
    {
        return $this->rewardLabel($this->referrer_reward_type, $this->referrer_reward_value);
    }

    public function referredRewardLabel(): string
    {
        return $this->rewardLabel($this->referred_reward_type, $this->referred_reward_value);
    }
}
