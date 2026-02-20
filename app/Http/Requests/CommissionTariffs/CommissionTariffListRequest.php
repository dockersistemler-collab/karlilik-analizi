<?php

namespace App\Http\Requests\CommissionTariffs;

use Illuminate\Foundation\Http\FormRequest;

class CommissionTariffListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }
}
