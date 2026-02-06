<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalBillingSuccessFlashTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_page_shows_success_flash_on_card_update(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $this->actingAs($user)
            ->get(route('portal.billing', ['card_update' => 'success']))
            ->assertOk()
            ->assertSee('Kart guncellendi, odeme tekrar denenecek.');
    }
}
