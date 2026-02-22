<?php

namespace Database\Factories;

use App\Models\MarketplaceAccount;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarketplaceAccountFactory extends Factory
{
    protected $model = MarketplaceAccount::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'marketplace' => 'trendyol',
            'connector_key' => 'trendyol',
            'store_name' => fake()->company() . ' Store',
            'credentials' => ['api_key' => 'demo', 'api_secret' => 'demo'],
            'status' => 'active',
            'is_active' => true,
        ];
    }
}

