<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'user_id' => User::factory(),
            'audience_role' => null,
            'marketplace' => null,
            'source' => 'system',
            'type' => 'operational',
            'channel' => 'in_app',
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->paragraph(),
            'data' => null,
            'action_url' => null,
            'dedupe_key' => null,
            'group_key' => null,
            'read_at' => null,
        ];
    }

    public function configure()
    {
        return $this->afterMaking(function (Notification $notification): void {
            if (!$notification->tenant_id && $notification->user_id) {
                $notification->tenant_id = $notification->user_id;
            }
        })->afterCreating(function (Notification $notification): void {
            if (!$notification->tenant_id && $notification->user_id) {
                $notification->tenant_id = $notification->user_id;
                $notification->save();
            }
        });
    }
}
