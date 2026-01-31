<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'marketplace_id',
        'marketplace_category_id',
        'marketplace_category_external_id',
        'source',
        'confidence',
    ];

    protected $casts = [
        'confidence' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function marketplace()
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function marketplaceCategory()
    {
        return $this->belongsTo(MarketplaceCategory::class);
    }
}

