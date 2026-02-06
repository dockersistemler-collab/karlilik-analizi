<?php

namespace Tests\Feature;

use App\Models\BillingSubscription;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalPastDueCtaTest extends TestCase
{
    use RefreshDatabase;

    public function test_past_due_subscription_shows_warning_and_cta(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'email_verified_at' => now(),
        ]);

        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-'.uniqid(),
            'price' => 0,
            'billing_period' => 'monthly',
            'is_active' => true,
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'amount' => 0,
            'billing_period' => 'monthly',
            'auto_renew' => false,
            'usage_reset_at' => now()->addMonth(),
        ]);

        BillingSubscription::create([
            'tenant_id' => $user->id,
            'user_id' => $user->id,
            'provider' => 'iyzico',
            'plan_code' => 'pro',
            'status' => 'PAST_DUE',
            'past_due_since' => now()->subDay(),
            'grace_until' => now()->addDays(2),
        ]);

        $this->actingAs($user)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('Odeme alinamadi')
            ->assertSee('Kart Guncelle');
    }
}
