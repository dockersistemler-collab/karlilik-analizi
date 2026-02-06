<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class InvoiceDownloadSignedTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsigned_download_is_forbidden(): void
    {
        $tenant = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $invoice = Invoice::create([
            'user_id' => $tenant->id,
            'subscription_id' => null,
            'invoice_number' => 'INV-1001',
            'amount' => 100,
            'currency' => 'TRY',
            'status' => 'paid',
            'issued_at' => now(),
        ]);

        $this->actingAs($tenant)
            ->get(route('portal.invoices.download', $invoice))
            ->assertStatus(403);
    }

    public function test_signed_download_returns_file(): void
    {
        $tenant = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $invoice = Invoice::create([
            'user_id' => $tenant->id,
            'subscription_id' => null,
            'invoice_number' => 'INV-1002',
            'amount' => 100,
            'currency' => 'TRY',
            'status' => 'paid',
            'issued_at' => now(),
        ]);

        $url = URL::signedRoute('portal.invoices.download', $invoice);

        $this->actingAs($tenant)
            ->get($url)
            ->assertOk();
    }
}
