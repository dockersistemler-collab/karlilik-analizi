<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\MailTemplateSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
public function run(): void
{
    $this->call([
        MarketplaceSeeder::class,
        PlanSeeder::class,
        ModuleCatalogSeeder::class,
        SuperAdminSeeder::class,  // BUNU EKLE
        ProfitEngineDefaultsSeeder::class,
        MarketplaceRiskDefaultsSeeder::class,
        MailTemplateSeeder::class,
        HakedisKontrolMerkeziSeeder::class,
        RbacPermissionSeeder::class,
    ]);
}
}
