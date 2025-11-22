<?php

namespace App\Http\Requests\Entities\Customers;

use App\Enums\RequestInputTranslateEnum;
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\CustomerFields;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required'],
            'mobilePhone' => ['required'],
            'cpfCnpj'       => ['nullable', 'string', 'max:191'],
            'postalCode'    => ['nullable', 'string', 'max:191'],
            'address'       => ['nullable', 'string', 'max:191'],
            'addressNumber' => ['nullable', 'string', 'max:191'],
            'province'      => ['nullable', 'string', 'max:191'],
            'cityName'      => ['nullable', 'string', 'max:191'],
            'state'         => ['nullable', 'string', 'max:191'],
        ];
    }

    public function messages()
    {
        $translatedFields = [
            'name',
            'mobilePhone',
            'cpfCnpj',
            'postalCode',
            'address',
            'addressNumber',
            'province',
            'cityName',
            'state'
        ];

        $messages = [];

        foreach ($translatedFields as $field) {
            $translatedField = RequestInputTranslateEnum::getTranslation($field);

            $messages["$field.required"] = "O $translatedField do cliente é obrigatório.";
        }

        return $messages;
    }
}
