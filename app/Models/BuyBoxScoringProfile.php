<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuyBoxScoringProfile extends Model
{
    use HasFactory;

    protected $table = 'buybox_scoring_profiles';

    protected $fillable = [
        'tenant_id',
        'marketplace',
        'weights',
        'thresholds',
    ];

    protected $casts = [
        'weights' => 'array',
        'thresholds' => 'array',
    ];
}
