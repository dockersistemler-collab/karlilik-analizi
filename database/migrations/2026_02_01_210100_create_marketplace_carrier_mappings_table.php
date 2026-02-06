<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_carrier_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('marketplace_code')->index();
            $table->string('external_carrier_code');
            $table->string('provider_key');
            $table->integer('priority')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['marketplace_code', 'external_carrier_code'], 'mkt_carrier_map_mkt_carrier_idx');
            $table->index(['provider_key'], 'mkt_carrier_map_provider_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_carrier_mappings');
    }
};
