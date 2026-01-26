<?php

namespace App\Domain\Tickets\Models;

use App\Domain\Tickets\Models\Concerns\CustomerScoped;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketMessage extends Model
{
    use HasFactory;
    use CustomerScoped;

    public const SENDER_CUSTOMER = 'customer';
    public const SENDER_ADMIN = 'admin';
    public const SENDER_SYSTEM = 'system';

    protected $fillable = [
        'ticket_id',
        'customer_id',
        'sender_type',
        'sender_id',
        'body',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }
}
