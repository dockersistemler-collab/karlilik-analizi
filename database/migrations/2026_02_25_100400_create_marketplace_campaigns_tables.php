<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('marketplace', 50)->index();
            $table->string('campaign_id', 80)->index();
            $table->string('name', 190)->nullable();
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->string('source', 20)->default('import');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'marketplace', 'campaign_id'], 'marketplace_campaigns_unique');
        });

        Schema::create('marketplace_campaign_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('marketplace_campaigns')->cascadeOnDelete();
            $table->string('sku', 120)->index();
            $table->decimal('discount_rate', 8, 4)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'sku'], 'marketplace_campaign_items_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_campaign_items');
        Schema::dropIfExists('marketplace_campaigns');
    }
};

