<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'set_num' => ['required', 'string', 'max:20'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'purchase_date' => ['nullable', 'date'],
            'condition' => ['required', 'in:new,opened,built'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
