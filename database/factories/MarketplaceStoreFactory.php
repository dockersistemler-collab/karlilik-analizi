<?php

namespace Database\Factories;

use App\Models\Marketplace;
use App\Models\MarketplaceStore;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketplaceStore>
 */
class MarketplaceStoreFactory extends Factory
{
    protected $model = MarketplaceStore::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'marketplace_id' => function () {
                $id = Marketplace::query()->inRandomOrder()->value('id');
                if ($id) {
                    return $id;
                }

                return Marketplace::query()->create([
                    'name' => 'Trendyol',
                    'code' => 'trendyol',
                    'is_active' => true,
                ])->id;
            },
            'store_name' => 'Store ' . $this->faker->unique()->word(),
            'store_external_id' => (string) $this->faker->unique()->numberBetween(10000, 99999),
            'credentials' => [
                'api_key' => $this->faker->sha1(),
                'secret' => $this->faker->sha1(),
            ],
            'is_active' => true,
        ];
    }
}
