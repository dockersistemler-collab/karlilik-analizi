<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'einvoice_id',
        'sku',
        'name',
        'quantity',
        'unit_price',
        'vat_rate',
        'vat_amount',
        'discount_amount',
        'total',
        'meta',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'meta' => 'array',
    ];

    public function einvoice()
    {
        return $this->belongsTo(EInvoice::class, 'einvoice_id');
    }
}


