<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('marketplace_products', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id')->constrained()->onDelete('cascade');
        $table->foreignId('marketplace_id')->constrained()->onDelete('cascade');
        $table->string('marketplace_product_id')->nullable(); // Pazaryerindeki ürün ID
        $table->string('marketplace_sku')->nullable(); // Pazaryerindeki SKU
        $table->decimal('price', 10, 2); // Pazaryerindeki fiyat
        $table->integer('stock_quantity'); // Pazaryerindeki stok
        $table->string('status')->default('draft'); // draft, active, inactive, rejected
        $table->text('rejection_reason')->nullable();
        $table->decimal('commission_rate', 5, 2)->nullable(); // Komisyon oranı %
        $table->string('listing_url')->nullable(); // Ürün linki
        $table->json('marketplace_data')->nullable(); // Pazaryerine özel ekstra data
        $table->timestamp('last_sync_at')->nullable();
        $table->boolean('auto_sync')->default(true); // Otomatik senkronizasyon
        $table->timestamps();
        
        // Unique constraint: Bir ürün bir pazaryerinde sadece bir kez olabilir
        $table->unique(['product_id', 'marketplace_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_products');
    }
};
