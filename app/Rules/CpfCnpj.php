<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class CpfCnpj implements Rule
{
    public function passes($attribute, $value): bool
    {
        $num = preg_replace('/\D/', '', $value ?? '');

        if (!in_array(strlen($num), [11, 14])) {
            return false;
        }

        // bloqueia sequências tipo 000..., 111...
        if (preg_match('/^(\d)\1+$/', $num)) {
            return false;
        }

        return strlen($num) === 11
            ? $this->validaCpf($num)
            : $this->validaCnpj($num);
    }

    public function message(): string
    {
        return 'CPF/CNPJ inválido. Confira os números e tente novamente.';
    }

    private function validaCpf(string $cpf): bool
    {
        if (strlen($cpf) != 11) {
            return false;
        }

        // 1º dígito
        $sum = 0;
        for ($i = 0, $peso = 10; $i < 9; $i++, $peso--) {
            $sum += $cpf[$i] * $peso;
        }
        $resto = ($sum * 10) % 11;
        $dv1 = $resto == 10 ? 0 : $resto;

        if ((int)$cpf[9] !== $dv1) {
            return false;
        }

        // 2º dígito
        $sum = 0;
        for ($i = 0, $peso = 11; $i < 10; $i++, $peso--) {
            $sum += $cpf[$i] * $peso;
        }
        $resto = ($sum * 10) % 11;
        $dv2 = $resto == 10 ? 0 : $resto;

        return (int)$cpf[10] === $dv2;
    }

    private function validaCnpj(string $cnpj): bool
    {
        if (strlen($cnpj) != 14) {
            return false;
        }

        // 1º dígito
        $weights1 = [5,4,3,2,9,8,7,6,5,4,3,2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnpj[$i] * $weights1[$i];
        }
        $resto = $sum % 11;
        $dv1 = $resto < 2 ? 0 : 11 - $resto;

        if ((int)$cnpj[12] !== $dv1) {
            return false;
        }

        // 2º dígito
        $weights2 = [6,5,4,3,2,9,8,7,6,5,4,3,2];
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += $cnpj[$i] * $weights2[$i];
        }
        $resto = $sum % 11;
        $dv2 = $resto < 2 ? 0 : 11 - $resto;

        return (int)$cnpj[13] === $dv2;
    }
}
