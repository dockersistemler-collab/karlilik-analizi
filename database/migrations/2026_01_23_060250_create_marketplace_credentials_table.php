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
        Schema::create('marketplace_credentials', function (Blueprint $table) {
            $table->id();
            
            // Hangi kullanıcıya ait? (Ahmet mi, Mehmet mi?)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Hangi pazaryeri? (Trendyol mu, N11 mi?)
            $table->foreignId('marketplace_id')->constrained()->onDelete('cascade');
            
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->string('supplier_id')->nullable(); // Satıcı ID (Trendyol vb. için)
            $table->string('store_id')->nullable(); // Mağaza ID
            $table->text('access_token')->nullable(); // OAuth token'lar için
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('extra_credentials')->nullable(); // Ekstra bilgiler için
            $table->timestamps();
            
            // Bir kullanıcı, aynı pazaryerinden (örn: Trendyol) sadece 1 tane hesap ekleyebilsin istiyorsan:
            // $table->unique(['user_id', 'marketplace_id']); 
            // (Şimdilik bunu yorum satırı yapıyorum, ileride çoklu mağaza istersen engel olmasın)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_credentials');
    }
};