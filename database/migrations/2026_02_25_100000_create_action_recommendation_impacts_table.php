<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('action_recommendation_impacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recommendation_id')->constrained('action_recommendations')->cascadeOnDelete();
            $table->json('baseline')->nullable();
            $table->json('scenario')->nullable();
            $table->json('expected')->nullable();
            $table->json('delta')->nullable();
            $table->decimal('confidence', 8, 4)->default(0);
            $table->json('assumptions')->nullable();
            $table->decimal('risk_effect', 8, 4)->default(0);
            $table->dateTime('calculated_at')->nullable();
            $table->timestamps();

            $table->unique('recommendation_id', 'action_recommendation_impacts_recommendation_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_recommendation_impacts');
    }
};

