<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    protected $fillable = [
        'referrer_id',
        'referred_user_id',
        'referred_email',
        'program_id',
        'status',
        'referrer_reward_type',
        'referrer_reward_value',
        'referred_reward_type',
        'referred_reward_value',
        'applied_discount_amount',
        'referrer_discount_amount',
        'referrer_discount_consumed_at',
        'rewarded_at',
    ];

    protected $casts = [
        'rewarded_at' => 'datetime',
        'referrer_reward_value' => 'decimal:2',
        'referred_reward_value' => 'decimal:2',
        'applied_discount_amount' => 'decimal:2',
        'referrer_discount_amount' => 'decimal:2',
        'referrer_discount_consumed_at' => 'datetime',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ReferralProgram::class, 'program_id');
    }
}
