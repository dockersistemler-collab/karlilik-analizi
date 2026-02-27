<?php

namespace Database\Factories;

use App\Models\CommunicationThread;
use App\Models\Marketplace;
use App\Models\MarketplaceStore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunicationThread>
 */
class CommunicationThreadFactory extends Factory
{
    protected $model = CommunicationThread::class;

    public function definition(): array
    {
        return [
            'marketplace_store_id' => MarketplaceStore::factory(),
            'marketplace_id' => function (array $attributes) {
                $store = MarketplaceStore::query()->find($attributes['marketplace_store_id'] ?? null);
                return $store?->marketplace_id ?? Marketplace::query()->value('id');
            },
            'channel' => $this->faker->randomElement(['question', 'message', 'review']),
            'external_thread_id' => 'thr_' . $this->faker->unique()->numerify('######'),
            'subject' => $this->faker->sentence(4),
            'product_name' => $this->faker->words(3, true),
            'product_sku' => strtoupper($this->faker->bothify('SKU-###??')),
            'customer_name' => $this->faker->name(),
            'status' => $this->faker->randomElement(['open', 'pending', 'answered']),
            'priority_score' => $this->faker->numberBetween(10, 200),
            'due_at' => now()->addMinutes($this->faker->numberBetween(15, 180)),
            'last_inbound_at' => now()->subMinutes($this->faker->numberBetween(5, 120)),
            'meta' => [
                'source' => 'factory',
            ],
        ];
    }
}
