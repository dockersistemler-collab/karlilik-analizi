<?php

namespace App\Http\Requests\CommissionTariffs;

use Illuminate\Foundation\Http\FormRequest;

class CommissionTariffAssignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'marketplace' => ['nullable', 'string', 'max:120'],
            'productId' => ['required', 'integer', 'exists:products,id'],
            'variantIds' => ['nullable', 'array'],
            'variantIds.*' => ['integer', 'exists:product_variants,id'],
            'allVariants' => ['nullable', 'boolean'],
            'ranges' => ['required', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasVariants = is_array($this->input('variantIds')) && count($this->input('variantIds')) > 0;
            $allVariants = (bool) $this->input('allVariants');

            if (!$hasVariants && !$allVariants) {
                $validator->errors()->add('variantIds', 'Varyant secimi gerekli.');
            }
        });
    }
}
