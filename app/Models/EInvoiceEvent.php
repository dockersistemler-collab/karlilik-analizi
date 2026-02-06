<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EInvoiceEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'einvoice_id',
        'type',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function einvoice()
    {
        return $this->belongsTo(EInvoice::class, 'einvoice_id');
    }
}


