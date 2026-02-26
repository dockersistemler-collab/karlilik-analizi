<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActionRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'date',
        'marketplace',
        'sku',
        'severity',
        'title',
        'description',
        'action_type',
        'suggested_payload',
        'reason',
        'status',
        'decided_at',
        'decided_by',
    ];

    protected $casts = [
        'date' => 'date',
        'suggested_payload' => 'array',
        'reason' => 'array',
        'decided_at' => 'datetime',
    ];

    public function decider()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function impact()
    {
        return $this->hasOne(ActionRecommendationImpact::class, 'recommendation_id');
    }
}
