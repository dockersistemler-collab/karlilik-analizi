<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductStockUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Product $product,
        public readonly int $oldQuantity,
        public readonly int $newQuantity,
        public readonly ?int $actorId = null
    ) {
    }
}
