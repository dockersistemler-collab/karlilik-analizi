<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CargoProviderInstallation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider_key',
        'credentials_json',
        'is_active',
    ];

    protected $casts = [
        'credentials_json' => 'encrypted:array',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
