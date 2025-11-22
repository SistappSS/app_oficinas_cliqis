<?php

namespace App\Http\Requests\Auth\Onboarding;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\CpfCnpj;

class StoreAdditionalCustomerInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Etapa 1 – formulário de informações da empresa
        if ($this->routeIs('additional-customer-info.store')) {
            return [
                'company_name'   => ['required', 'string', 'max:255'],
                'cpfCnpj'        => ['required', new CpfCnpj],
                'mobilePhone'    => ['required', 'string', 'max:20'],
                'company_email'  => ['required', 'email', 'max:255'],
                'postalCode'     => ['nullable', 'string', 'max:9'],
                'address'        => ['nullable', 'string', 'max:255'],
                'addressNumber'  => ['nullable', 'string', 'max:20'],
                'complement'     => ['nullable', 'string', 'max:255'],
                'cityName'       => ['nullable', 'string', 'max:255'],
                'province'       => ['nullable', 'string', 'max:255'],
                'state'          => ['nullable', 'string', 'max:2'],
                'website_url'    => ['nullable', 'string', 'max:255'],
                'image'          => ['nullable', 'image', 'max:1024'], // 1MB
            ];
        }

        if ($this->routeIs('addons.store')) {
            return [
                'selected' => ['required'],
                'total'    => ['required', 'numeric', 'min:0'],
            ];
        }

        return [];
    }
}
