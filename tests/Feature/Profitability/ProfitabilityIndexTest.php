<?php

namespace Tests\Feature\Profitability;

use App\Models\CoreOrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfitabilityIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_profitability_index_renders(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        CoreOrderItem::query()->create([
            'tenant_id' => $user->id,
            'marketplace' => 'trendyol',
            'order_id' => 'O-1',
            'order_item_id' => 'OI-1',
            'order_date' => now(),
            'quantity' => 1,
            'currency' => 'TRY',
            'fx_rate' => 1,
            'gross_sales' => 100,
            'discounts' => 10,
            'refunds' => 0,
            'net_sales' => 90,
            'commission_fee' => 5,
            'payment_fee' => 2,
            'shipping_fee' => 0,
            'other_fees' => 1,
            'fees_total' => 8,
            'cogs_total' => 40,
            'gross_profit' => 50,
            'contribution_margin' => 42,
            'status' => 'paid',
        ]);

        $this->withoutMiddleware()
            ->actingAs($user)
            ->get(route('portal.profitability.index'))
            ->assertOk()
            ->assertSee('Kârlılık Paneli');
    }
}
