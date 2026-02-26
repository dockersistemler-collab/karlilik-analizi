<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActionRecommendationImpact extends Model
{
    use HasFactory;

    protected $fillable = [
        'recommendation_id',
        'baseline',
        'scenario',
        'expected',
        'delta',
        'confidence',
        'assumptions',
        'risk_effect',
        'calculated_at',
    ];

    protected $casts = [
        'baseline' => 'array',
        'scenario' => 'array',
        'expected' => 'array',
        'delta' => 'array',
        'assumptions' => 'array',
        'confidence' => 'decimal:4',
        'risk_effect' => 'decimal:4',
        'calculated_at' => 'datetime',
    ];

    public function recommendation()
    {
        return $this->belongsTo(ActionRecommendation::class, 'recommendation_id');
    }
}

