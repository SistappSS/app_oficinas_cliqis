<?php

namespace App\Http\Requests\Entities\Suppliers;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required'],
            'document' => ['required'],
            'address' => ['required'],
            'postal_code' => ['required'],
            'city' => ['required'],
            'state_id' => ['required']
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'O campo de nome é obrigatório.',
            'document.required' => 'O campo de CNPJ/CPG é obrigatório.',
            'address.required' => 'O campo de Endereço é obrigatório.',
            'postal_code.required' => 'O campo de CEP é obrigatório.',
            'state.required' => 'O campo de Estado é obrigatório.',
            'city.required' => 'O campo de Cidade é obrigatório.',
            'state.required' => 'O campo de Estado é obrigatório',
        ];
    }
}
