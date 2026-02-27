<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('ai_enabled')->default(true);
            $table->string('notification_email')->nullable();
            $table->integer('cron_interval_minutes')->default(5);
            $table->json('priority_weights')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });

        DB::table('communication_settings')->insert([
            'user_id' => null,
            'ai_enabled' => true,
            'notification_email' => null,
            'cron_interval_minutes' => 5,
            'priority_weights' => json_encode([
                'time_left' => 3,
                'store_rating_risk' => 0,
                'sales_velocity' => 0,
                'margin' => 0,
                'buybox_critical' => 0,
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_settings');
    }
};

