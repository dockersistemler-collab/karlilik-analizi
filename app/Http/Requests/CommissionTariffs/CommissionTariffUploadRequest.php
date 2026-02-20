<?php

namespace App\Http\Requests\CommissionTariffs;

use Illuminate\Foundation\Http\FormRequest;

class CommissionTariffUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file'],
            'marketplace' => ['required', 'string', 'in:trendyol,hepsiburada'],
        ];
    }
}
