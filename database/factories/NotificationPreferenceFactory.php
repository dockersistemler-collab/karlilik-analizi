<?php

namespace Database\Factories;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationPreference>
 */
class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'user_id' => User::factory(),
            'type' => 'operational',
            'channel' => 'in_app',
            'marketplace' => null,
            'enabled' => true,
            'quiet_hours' => null,
        ];
    }

    public function configure()
    {
        return $this->afterMaking(function (NotificationPreference $preference): void {
            if (!$preference->tenant_id && $preference->user_id) {
                $preference->tenant_id = $preference->user_id;
            }
        })->afterCreating(function (NotificationPreference $preference): void {
            if (!$preference->tenant_id && $preference->user_id) {
                $preference->tenant_id = $preference->user_id;
                $preference->save();
            }
        });
    }
}
