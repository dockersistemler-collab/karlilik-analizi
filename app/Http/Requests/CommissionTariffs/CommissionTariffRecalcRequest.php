<?php

namespace App\Http\Requests\CommissionTariffs;

use Illuminate\Foundation\Http\FormRequest;

class CommissionTariffRecalcRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'productId' => ['required', 'integer', 'exists:products,id'],
            'variantId' => ['nullable', 'integer', 'exists:product_variants,id'],
            'manualPrice' => ['required', 'numeric', 'min:0'],
        ];
    }
}
