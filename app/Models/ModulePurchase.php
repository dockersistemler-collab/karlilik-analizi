<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModulePurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'module_id',
        'provider',
        'provider_payment_id',
        'amount',
        'currency',
        'period',
        'status',
        'starts_at',
        'ends_at',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function notificationLogs()
    {
        return $this->hasMany(NotificationLog::class);
    }
}
