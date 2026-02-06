<?php

namespace Tests\Feature;

use App\Models\BillingEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BillingCorrelationIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_correlation_header_is_propagated(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-02-05 10:00:00'));

        $tenant = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $payload = [
            'customer_name' => 'Test',
            'customer_email' => 'test@example.com',
            'billing_address' => 'Test',
            'amount' => 100,
            'currency' => 'TRY',
            'status' => 'paid',
            'issued_at' => now()->toDateString(),
        ];

        $correlationId = 'corr-123';

        $this->actingAs($tenant)
            ->withHeader('X-Correlation-Id', $correlationId)
            ->post(route('portal.invoices.store'), $payload)
            ->assertRedirect(route('portal.invoices.index'));

        $events = BillingEvent::query()->where('tenant_id', $tenant->id)->get();
        $this->assertNotEmpty($events);
        foreach ($events as $event) {
            $this->assertSame($correlationId, $event->correlation_id);
        }
    }

    public function test_correlation_is_generated_when_missing(): void
    {
        $tenant = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $payload = [
            'customer_name' => 'Test',
            'customer_email' => 'test@example.com',
            'billing_address' => 'Test',
            'amount' => 100,
            'currency' => 'TRY',
            'status' => 'paid',
            'issued_at' => now()->toDateString(),
        ];

        $this->actingAs($tenant)
            ->post(route('portal.invoices.store'), $payload)
            ->assertRedirect(route('portal.invoices.index'));

        $event = BillingEvent::query()->where('tenant_id', $tenant->id)->latest('id')->first();
        $this->assertNotNull($event);
        $this->assertNotEmpty($event->correlation_id);
    }
}

