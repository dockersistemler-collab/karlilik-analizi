<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('city')->nullable()->after('email');
            $table->string('district')->nullable()->after('city');
            $table->string('neighborhood')->nullable()->after('district');
            $table->string('street')->nullable()->after('neighborhood');
            $table->string('customer_type')->default('individual')->after('billing_address');
            $table->string('tax_id')->nullable()->after('customer_type');
            $table->string('tax_office')->nullable()->after('tax_id');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'city',
                'district',
                'neighborhood',
                'street',
                'customer_type',
                'tax_id',
                'tax_office',
            ]);
        });
    }
};
