<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('plans', 'plan_modules')) {
            DB::table('plans')->update(['plan_modules' => null]);
        }

        $plans = DB::table('plans')->select(['id', 'features'])->get();
        foreach ($plans as $plan) {
            $features = json_decode($plan->features ?? '', true);
            if (!is_array($features)) {
                continue;
            }

            $dirty = false;
            foreach (['plan_modules', 'planModules'] as $key) {
                if (array_key_exists($key, $features)) {
                    unset($features[$key]);
                    $dirty = true;
                }
            }

            if (!$dirty) {
                continue;
            }

            DB::table('plans')
                ->where('id', $plan->id)
                ->update(['features' => json_encode($features, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        }
    }

    public function down(): void
    {
        // no-op: legacy plan_modules should stay cleared
    }
};
