<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purchase_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'purchase_date' => ['sometimes', 'nullable', 'date'],
            'condition' => ['sometimes', 'required', 'in:new,opened,built'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
