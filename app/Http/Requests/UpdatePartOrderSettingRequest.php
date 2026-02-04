<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePartOrderSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // se tiver policy, troca aqui
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'billing_uf'        => $this->billing_uf ? strtoupper(trim((string) $this->billing_uf)) : null,
            'billing_cnpj'      => $this->billing_cnpj ? trim((string) $this->billing_cnpj) : null,
            'default_supplier_id' => $this->default_supplier_id ? trim((string) $this->default_supplier_id) : null,
            'email_subject_tpl' => $this->email_subject_tpl !== null ? trim((string) $this->email_subject_tpl) : null,
            'email_body_tpl'    => $this->email_body_tpl !== null ? trim((string) $this->email_body_tpl) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'default_supplier_id' => ['nullable', 'string', 'max:36'],
            'billing_cnpj'        => ['nullable', 'string', 'max:20'],
            'billing_uf'          => ['nullable', 'string', 'size:2'],

            'email_subject_tpl'   => ['nullable', 'string', 'max:200'],
            'email_body_tpl'      => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'billing_uf.size' => 'UF deve ter 2 letras.',
        ];
    }
}
