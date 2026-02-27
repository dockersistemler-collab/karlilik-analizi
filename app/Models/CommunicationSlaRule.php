<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunicationSlaRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace_id',
        'channel',
        'sla_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sla_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function marketplace()
    {
        return $this->belongsTo(Marketplace::class);
    }
}

