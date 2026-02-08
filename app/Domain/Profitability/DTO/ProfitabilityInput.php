<?php

namespace App\Domain\Profitability\DTO;

/**
 * Input payload for profitability calculation.
 */
class ProfitabilityInput
{
    public ?int $order_id;
    public ?string $order_number;
    public string $order_date;
    public string $sale_price;
    public string $commission_amount;
    /** @var array<int, array<string, mixed>> */
    public array $items;
    /** @var array<string, mixed> */
    public array $marketplace_data;
    public ?int $user_id;

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $marketplace_data
     */
    public function __construct(
        ?int $order_id,
        ?string $order_number,
        string $order_date,
        string $sale_price,
        string $commission_amount,
        array $items,
        array $marketplace_data,
        ?int $user_id = null
    ) {
        $this->order_id = $order_id;
        $this->order_number = $order_number;
        $this->order_date = $order_date;
        $this->sale_price = $sale_price;
        $this->commission_amount = $commission_amount;
        $this->items = $items;
        $this->marketplace_data = $marketplace_data;
        $this->user_id = $user_id;
    }
}
