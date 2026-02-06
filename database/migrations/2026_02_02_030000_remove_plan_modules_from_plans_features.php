<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $plans = DB::table('plans')->select(['id', 'features'])->get();

        foreach ($plans as $plan) {
            $features = json_decode($plan->features ?? '', true);
            if (!is_array($features) || !array_key_exists('plan_modules', $features)) {
                continue;
            }

            unset($features['plan_modules']);
            DB::table('plans')
                ->where('id', $plan->id)
                ->update(['features' => json_encode($features, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        }
    }

    public function down(): void
    {
        // no-op: plan_modules is deprecated and should not be restored
    }
};
