<?php

namespace App\Domains\Settlements\Models;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderFinancialItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'marketplace',
        'type',
        'gross_amount',
        'vat_amount',
        'net_amount',
        'currency',
        'source',
        'raw_ref',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'raw_ref' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

