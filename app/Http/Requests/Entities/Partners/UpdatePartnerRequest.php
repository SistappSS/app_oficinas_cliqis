<?php

namespace App\Http\Requests\Entities\Partners;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePartnerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required'],
            'salary_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages()
    {
        return [
            'user_id.required' => 'Selecione um usuário.',
            'salary_percent.required' => 'O campo de porcentagem do salário é obrigatório.',
            'salary_percent.max' => 'Esse campo não aceita valor maior que 100.',
            'salary_percent.min' => 'Esse campo não aceita valor menor que 0.'
        ];
    }
}
