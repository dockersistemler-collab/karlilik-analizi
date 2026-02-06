<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EInvoiceApiDocsTest extends TestCase
{
    use RefreshDatabase;

    public function test_docs_page_is_accessible(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $this->actingAs($user)
            ->get(route('portal.docs.einvoice'))
            ->assertOk()
            ->assertSee('E-Fatura API');
    }

    public function test_openapi_can_be_downloaded(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($user)->get(route('portal.docs.einvoice.openapi'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/yaml; charset=UTF-8');
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
    }

    public function test_postman_collection_can_be_downloaded(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($user)->get(route('portal.docs.einvoice.postman'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/json; charset=UTF-8');
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
    }
}


