<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EInvoiceProviderInstallation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider_key',
        'status',
        'credentials',
        'settings',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'settings' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

