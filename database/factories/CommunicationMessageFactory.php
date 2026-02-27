<?php

namespace Database\Factories;

use App\Models\CommunicationMessage;
use App\Models\CommunicationThread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunicationMessage>
 */
class CommunicationMessageFactory extends Factory
{
    protected $model = CommunicationMessage::class;

    public function definition(): array
    {
        return [
            'thread_id' => CommunicationThread::factory(),
            'direction' => $this->faker->randomElement(['inbound', 'outbound']),
            'body' => $this->faker->sentence(12),
            'created_at_external' => now()->subMinutes($this->faker->numberBetween(1, 180)),
            'sender_type' => $this->faker->randomElement(['customer', 'seller']),
            'ai_suggested' => false,
            'meta' => [
                'source' => 'factory',
            ],
        ];
    }
}

