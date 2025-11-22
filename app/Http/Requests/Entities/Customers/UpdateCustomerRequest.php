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
            'name' => ['required'],
            'cpfCnpj' => ['required'],
            'mobilePhone' => ['required'],
            'address' => ['required'],
            'addressNumber' => ['required'],
            'postalCode' => ['required']
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'O campo de nome é obrigatório.',
            'address.required' => 'O campo de Endereço é obrigatório.',
            'postalCode.required' => 'O campo de CEP é obrigatório.',
        ];
    }
}
