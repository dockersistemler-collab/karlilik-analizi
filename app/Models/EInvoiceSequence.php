<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EInvoiceSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'prefix',
        'last_number',
    ];

    protected $casts = [
        'year' => 'integer',
        'last_number' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

