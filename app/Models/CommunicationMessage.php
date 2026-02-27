<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunicationMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'thread_id',
        'direction',
        'body',
        'created_at_external',
        'sender_type',
        'ai_suggested',
        'sent_by_user_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'created_at_external' => 'datetime',
            'ai_suggested' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function thread()
    {
        return $this->belongsTo(CommunicationThread::class, 'thread_id');
    }

    public function sentByUser()
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}

