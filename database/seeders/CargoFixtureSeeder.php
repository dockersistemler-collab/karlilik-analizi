<?php

namespace Database\Seeders;

use App\Models\CargoProviderInstallation;
use App\Models\MarketplaceCarrierMapping;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CargoFixtureSeeder extends Seeder
{
    public function run(): void
    {
        $userId = 2;
        $user = DB::table('users')->where('id', $userId)->first();

        if (!$user) {
            DB::table('users')->insert([
                'id' => $userId,
                'name' => 'Cargo Fixture User',
                'email' => 'cargo-fixture@example.com',
                'role' => 'client',
                'is_active' => true,
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $mappings = [
            [
                'marketplace_code' => 'trendyol',
                'external_carrier_code' => 'Trendyol Express',
                'provider_key' => 'trendyol_express',
                'priority' => null,
                'is_active' => true,
            ],
            [
                'marketplace_code' => 'trendyol',
                'external_carrier_code' => 'Aras',
                'provider_key' => 'aras',
                'priority' => null,
                'is_active' => true,
            ],
        ];

        foreach ($mappings as $mapping) {
            MarketplaceCarrierMapping::query()->updateOrCreate(
                [
                    'marketplace_code' => $mapping['marketplace_code'],
                    'external_carrier_code' => $mapping['external_carrier_code'],
                ],
                $mapping
            );
        }

        $installations = [
            'trendyol_express',
            'aras',
        ];

        foreach ($installations as $providerKey) {
            CargoProviderInstallation::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'provider_key' => $providerKey,
                ],
                [
                    'credentials_json' => ['test' => true],
                    'is_active' => true,
                ]
            );
        }
    }
}
