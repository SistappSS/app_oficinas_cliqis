<?php

namespace App\Http\Requests\Entities\Partners;

use Illuminate\Foundation\Http\FormRequest;

class StorePartnerRequest extends FormRequest
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
            'salary_percent' => ['required', 'min:0', 'max:100'],
        ];
    }

    public function messages()
    {
        return [
            'user_id.required' => '<i class="fa-solid fa-bell"></i> Selecione um usuário.',
            'salary_percent.required' => '<i class="fa-solid fa-bell"></i> O campo de porcentagem do salário é obrigatório.',
            'salary_percent.max' => '<i class="fa-solid fa-bell"></i> Esse campo não aceita valor maior que 100.',
            'salary_percent.min' => '<i class="fa-solid fa-bell"></i> Esse campo não aceita valor menor que 0.'
        ];
    }
}
