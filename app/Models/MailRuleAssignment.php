<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailRuleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'scope_type',
        'scope_id',
        'key',
        'allowed',
        'daily_limit',
        'monthly_limit',
    ];

    protected function casts(): array
    {
        return [
            'allowed' => 'boolean',
            'daily_limit' => 'integer',
            'monthly_limit' => 'integer',
        ];
    }
}
