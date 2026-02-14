<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'type',
        'quantity_change',
        'meta_json',
        'created_by',
    ];

    protected $casts = [
        'meta_json' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
