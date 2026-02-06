<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'user_id',
        'status',
        'provider_message_id',
        'error',
        'metadata_json',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata_json' => 'array',
            'sent_at' => 'datetime',
        ];
    }
}
