<?php

namespace App\Http\Requests\Sales\Services;

use App\Enums\TypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validTypeValues = collect(TypeEnum::cases())->map(fn($enum) => $enum->value);

        return [
            'name' => ['required'],
            'price' => ['required'],
            'description' => ['required'],
            'type' => ['required', Rule::in($validTypeValues)],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Insira um nome para esse item.',
            'price.required' => 'Insira um preço para esse item.',
            'description.required' => 'Insira uma descrição para esse serviço.',
            'type.required' => 'Insira um tipo para esse item.'
        ];
    }
}
