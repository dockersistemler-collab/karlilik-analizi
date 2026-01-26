<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class SubUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'owner_user_id',
        'name',
        'email',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function permissions()
    {
        return $this->hasMany(SubUserPermission::class);
    }

    public function hasPermission(string $permissionKey): bool
    {
        if ($this->permissions->contains('permission_key', $permissionKey)) {
            return true;
        }

        if (str_starts_with($permissionKey, 'reports.') && $this->permissions->contains('permission_key', 'reports')) {
            return true;
        }

        return false;
    }
}
