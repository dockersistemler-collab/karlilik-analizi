<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('modules')) {
            return;
        }

        $existing = DB::table('modules')
            ->where('code', 'customer_communication_center')
            ->first();

        $payload = [
            'name' => 'Müşteri İletişim Merkezi',
            'description' => 'Trendyol, Hepsiburada, Amazon ve N11 müşteri iletişimlerini tek ekranda toplar.',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('modules')
                ->where('id', $existing->id)
                ->update($payload);
            return;
        }

        DB::table('modules')->insert(array_merge($payload, [
            'code' => 'customer_communication_center',
            'created_at' => now(),
        ]));
    }

    public function down(): void
    {
        if (!Schema::hasTable('modules')) {
            return;
        }

        DB::table('modules')
            ->where('code', 'customer_communication_center')
            ->delete();
    }
};

