<?php

namespace App\Http\Requests\CommissionTariffs;

use Illuminate\Foundation\Http\FormRequest;

class CommissionTariffExportRequest extends FormRequest
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
            'selected' => ['nullable', 'array'],
            'selected.*' => ['integer'],
        ];
    }
}
