<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_user_id')->constrained('sub_users')->cascadeOnDelete();
            $table->string('permission_key', 80);
            $table->timestamps();

            $table->unique(['sub_user_id', 'permission_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_user_permissions');
    }
};
