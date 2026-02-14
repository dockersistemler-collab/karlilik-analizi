<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'alert_type',
        'threshold',
        'last_notified_at',
        'is_active',
    ];

    protected $casts = [
        'last_notified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
