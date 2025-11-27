<?php

namespace App\Http\Requests\Entities\Customers;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required']
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'O campo de nome é obrigatório.'
        ];
    }
}
