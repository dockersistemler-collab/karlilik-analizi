<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubUserPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'sub_user_id',
        'permission_key',
    ];

    public function subUser()
    {
        return $this->belongsTo(SubUser::class);
    }
}
