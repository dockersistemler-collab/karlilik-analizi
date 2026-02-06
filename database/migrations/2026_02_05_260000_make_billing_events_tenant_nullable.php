<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_events', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->change();
        });

        DB::table('billing_events')
            ->where('tenant_id', 0)
            ->update(['tenant_id' => null]);
    }

    public function down(): void
    {
        DB::table('billing_events')
            ->whereNull('tenant_id')
            ->update(['tenant_id' => 0]);

        Schema::table('billing_events', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
        });
    }
};
