<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_carrier_mappings', function (Blueprint $table) {
            $table->json('meta')->nullable()->after('is_active');
        });

        Schema::table('cargo_provider_installations', function (Blueprint $table) {
            $table->json('meta')->nullable()->after('credentials_json');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_carrier_mappings', function (Blueprint $table) {
            $table->dropColumn('meta');
        });

        Schema::table('cargo_provider_installations', function (Blueprint $table) {
            $table->dropColumn('meta');
        });
    }
};
