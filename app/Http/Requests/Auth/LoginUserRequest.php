<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Auth;
use Illuminate\Support\Facades\DB;

class LoginUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', function ($attribute, $value, $fail) {
                if (!DB::table('users')->where('email', $value)->exists()) {
                    $fail('O e-mail informado não está cadastrado.');
                }
            }],
            'password' => ['required', 'string', function ($attribute, $value, $fail) {
                if (DB::table('users')->where('email', $this->input('email'))->exists()) {
                    if (!Auth::attempt(['email' => $this->input('email'), 'password' => $value])) {
                        $fail('Credenciais inválidas! Verifique seu e-mail ou senha.');
                    }
                }
            }],
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'O campo de e-mail é obrigatório.',
            'password.required' => 'O campo de senha é obrigatório.',

            'email.email' => 'Adicione um e-mail válido.',
            'password.min' => 'A precisa ter no mínimo 6 caracteres.'
        ];
    }
}
