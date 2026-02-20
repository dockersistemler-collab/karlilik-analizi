<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionTariffRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'upload_id',
        'row_no',
        'raw',
        'product_match_key',
        'variant_match_key',
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
        'status',
        'error_message',
    ];

    protected $casts = [
        'raw' => 'array',
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

    public function upload()
    {
        return $this->belongsTo(CommissionTariffUpload::class, 'upload_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
