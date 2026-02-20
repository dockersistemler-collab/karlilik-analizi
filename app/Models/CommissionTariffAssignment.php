<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionTariffAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace',
        'product_id',
        'variant_id',
        'range1_min',
        'range1_max',
        'c1_percent',
        'range2_min',
        'range2_max',
        'c2_percent',
        'range3_min',
        'range3_max',
        'c3_percent',
        'range4_min',
        'range4_max',
        'c4_percent',
    ];

    protected $casts = [
        'range1_min' => 'decimal:2',
        'range1_max' => 'decimal:2',
        'c1_percent' => 'decimal:2',
        'range2_min' => 'decimal:2',
        'range2_max' => 'decimal:2',
        'c2_percent' => 'decimal:2',
        'range3_min' => 'decimal:2',
        'range3_max' => 'decimal:2',
        'c3_percent' => 'decimal:2',
        'range4_min' => 'decimal:2',
        'range4_max' => 'decimal:2',
        'c4_percent' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
