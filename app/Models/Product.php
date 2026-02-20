<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sku',
        'barcode',
        'name',
        'description',
        'brand',
        'category',
        'category_id',
        'price',
        'cost_price',
        'stock_quantity',
        'critical_stock_level',
        'currency',
        'weight',
        'desi',
        'vat_rate',
        'image_url',
        'images',
        'attributes',
        'is_active',
    ];

    public function getDisplayImageUrlAttribute(): ?string
    {
        $url = $this->image_url;
        if (!$url && is_array($this->images) && count($this->images) > 0) {
            $url = $this->images[0];
        }

        if (!$url) {
            return null;
        }

        // Normalize Windows-style paths for browser URLs.
        $url = str_replace('\\', '/', (string) $url);

        if (Str::startsWith($url, ['http://', 'https://'])) {
            $path = parse_url($url, PHP_URL_PATH);
            if ($path && Str::startsWith($path, '/storage/')) {
                return $path;
            }
            return $url;
        }

        if (Str::startsWith($url, '/')) {
            return $url;
        }

        return '/storage/' . ltrim($url, '/');
    }

    protected $casts = [
        'images' => 'array',
        'attributes' => 'array',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'desi' => 'decimal:2',
        'vat_rate' => 'integer',
        'critical_stock_level' => 'integer',
    ];

    // İlişkiler
    public function marketplaceProducts()
    {
        return $this->hasMany(MarketplaceProduct::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categoryRelation()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function marketplaces()
    {
        return $this->belongsToMany(Marketplace::class, 'marketplace_products')
            ->withPivot('marketplace_product_id', 'price', 'stock_quantity', 'status')
            ->withTimestamps();
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockAlerts()
    {
        return $this->hasMany(StockAlert::class);
    }

    public function marketplaceListings()
    {
        return $this->hasMany(MarketplaceListing::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function commissionTariffAssignments()
    {
        return $this->hasMany(CommissionTariffAssignment::class);
    }
}

