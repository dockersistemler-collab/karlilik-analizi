<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CustomerInvoicePortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_portal_invoice_pages(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-02-05 10:00:00'));

        $tenant = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $invoice = Invoice::create([
            'user_id' => $tenant->id,
            'subscription_id' => null,
            'invoice_number' => 'INV-0001',
            'amount' => 100,
            'currency' => 'TRY',
            'status' => 'paid',
            'issued_at' => now(),
            'paid_at' => now(),
            'billing_name' => 'Test',
            'billing_email' => 'test@example.com',
            'billing_address' => 'Test',
        ]);

        $this->actingAs($tenant)
            ->get(route('portal.invoices.index'))
            ->assertOk();

        $this->actingAs($tenant)
            ->get(route('portal.invoices.show', $invoice))
            ->assertOk();
    }

    public function test_cross_tenant_invoice_access_is_blocked(): void
    {
        $tenant = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $other = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $invoice = Invoice::create([
            'user_id' => $other->id,
            'subscription_id' => null,
            'invoice_number' => 'INV-0002',
            'amount' => 100,
            'currency' => 'TRY',
            'status' => 'paid',
            'issued_at' => now(),
        ]);

        $this->actingAs($tenant)
            ->get(route('portal.invoices.show', $invoice))
            ->assertStatus(404);
    }
}
