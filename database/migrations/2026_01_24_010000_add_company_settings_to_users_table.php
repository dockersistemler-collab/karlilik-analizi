<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('billing_address');
            $table->string('company_slogan')->nullable()->after('company_name');
            $table->string('company_phone')->nullable()->after('company_slogan');
            $table->string('notification_email')->nullable()->after('company_phone');
            $table->text('company_address')->nullable()->after('notification_email');
            $table->string('company_website')->nullable()->after('company_address');
            $table->string('company_logo_path')->nullable()->after('company_website');
            $table->boolean('invoice_number_tracking')->default(false)->after('company_logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'company_slogan',
                'company_phone',
                'notification_email',
                'company_address',
                'company_website',
                'company_logo_path',
                'invoice_number_tracking',
            ]);
        });
    }
};
