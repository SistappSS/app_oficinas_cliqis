<?php

namespace App\Http\Requests\Entities\Representatives;

use App\Enums\RequestInputTranslateEnum;
use Illuminate\Foundation\Http\FormRequest;

class StoreRepresentativeRequest extends FormRequest
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
            'postalCode' => ['required'],
            'address' => ['required'],
            'addressNumber' => ['required'],
            'province' => ['required'],
            'cityName' => ['required'],
            'state' => ['required'],
        ];
    }

    public function messages()
    {
        $translatedFields = [
            'name', 'cpfCnpj', 'mobilePhone', 'postalCode', 'address', 'addressNumber', 'province', 'cityName', 'state'
        ];

        $messages = [];

        foreach ($translatedFields as $field) {
            $translatedField = RequestInputTranslateEnum::getTranslation($field);

            $messages["$field.required"] = "O $translatedField do representante é obrigatório.";
        }

        return $messages;
    }
}
