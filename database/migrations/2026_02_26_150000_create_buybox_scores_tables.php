<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buybox_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('marketplace', 50)->index();
            $table->date('date')->index();
            $table->string('sku', 191)->index();
            $table->unsignedSmallInteger('buybox_score');
            $table->string('status', 20);
            $table->decimal('win_probability', 6, 4)->nullable();
            $table->json('drivers')->nullable();
            $table->unsignedBigInteger('snapshot_id');
            $table->timestamps();

            $table->unique(['tenant_id', 'marketplace', 'date', 'sku'], 'buybox_scores_unique');
            $table->foreign('snapshot_id')
                ->references('id')
                ->on('marketplace_offer_snapshots')
                ->cascadeOnDelete();
        });

        Schema::create('buybox_scoring_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('marketplace', 50)->index();
            $table->json('weights');
            $table->json('thresholds');
            $table->timestamps();

            $table->unique(['tenant_id', 'marketplace'], 'buybox_scoring_profiles_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buybox_scoring_profiles');
        Schema::dropIfExists('buybox_scores');
    }
};

