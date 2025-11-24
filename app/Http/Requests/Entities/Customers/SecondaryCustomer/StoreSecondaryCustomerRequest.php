<?php

namespace App\Http\Requests\Entities\Customers\SecondaryCustomer;

use App\Enums\RequestInputTranslateEnum;
use Illuminate\Foundation\Http\FormRequest;

class StoreSecondaryCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required'],
            'cpfCnpj'       => ['nullable'],
            'mobilePhone' => ['nullable'],
            'postalCode'    => ['nullable'],
            'address'       => ['nullable'],
            'addressNumber' => ['nullable'],
            'province'      => ['nullable'],
            'cityName'      => ['nullable'],
            'state'         => ['nullable'],
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
