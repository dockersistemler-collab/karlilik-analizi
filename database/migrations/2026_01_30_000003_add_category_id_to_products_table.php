<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('category')
                ->constrained('categories')
                ->nullOnDelete();
        });

        // Best-effort backfill: match existing products.category (string) to categories.name for same user.
        if (Schema::hasColumn('products', 'user_id') && Schema::hasColumn('products', 'category')) {
            Product::query()
                ->whereNull('category_id')
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->select(['id', 'user_id', 'category'])
                ->chunkById(500, function ($products) {
                    foreach ($products as $product) {
                        $categoryId = Category::query()
                            ->where('user_id', $product->user_id)
                            ->where('name', $product->category)
                            ->value('id');
                        if ($categoryId) {
                            Product::query()->whereKey($product->id)->update([
                                'category_id' => $categoryId,
                            ]);
                        }
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};

