<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_rule_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('scope_type');
            $table->unsignedBigInteger('scope_id');
            $table->string('key');
            $table->boolean('allowed')->default(true);
            $table->integer('daily_limit')->nullable();
            $table->integer('monthly_limit')->nullable();
            $table->timestamps();

            $table->unique(['scope_type', 'scope_id', 'key'], 'mail_rule_assignments_scope_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_rule_assignments');
    }
};
