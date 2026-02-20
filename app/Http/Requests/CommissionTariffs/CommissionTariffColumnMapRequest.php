<?php

namespace App\Http\Requests\CommissionTariffs;

use Illuminate\Foundation\Http\FormRequest;

class CommissionTariffColumnMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uploadId' => ['required', 'integer', 'exists:commission_tariff_uploads,id'],
            'mapping' => ['required', 'array'],
        ];
    }
}
