<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use App\Models\Entities\Customers\CustomerEmployeeUser;

final class CustomerContext
{
    private static ?string $id = null;
    private static bool $bypass = false;

    public static function set(?string $id): void
    {
        static::$id = $id;
    }

    public static function get(): ?string
    {
        // se já foi setado (via login, impersonate, for(), etc), só devolve
        if (static::$id !== null) {
            return static::$id;
        }

        // se estiver em bypass, não resolve nada (scope não será aplicado)
        if (static::$bypass) {
            return null;
        }

        $user = Auth::user();
        if (! $user) {
            return null;
        }

        // 1) CUSTOMER PRINCIPAL (customer_user_login)
        // ajuste esse relacionamento pro que você já usa hoje (ex.: customerLogin)
        $login = $user->customerLogin ?? null;
        if ($login && $login->customer_sistapp_id) {
            static::$id = $login->customer_sistapp_id;
            return static::$id;
        }

        // 2) EMPLOYEE (técnico) -> tabela customer_employee_users
        $link = CustomerEmployeeUser::where('user_id', $user->id)->first();

        if ($link) {
            // ajuste o nome da coluna conforme sua tabela:
            // se for "customer_sistapp_id", ótimo. Se for "customer_id", troca aqui.
            static::$id = $link->customer_sistapp_id ?? $link->customer_id;
            return static::$id;
        }

        return null;
    }

    public static function bypass(bool $state = true): void
    {
        static::$bypass = $state;
    }

    public static function isBypassed(): bool
    {
        return static::$bypass;
    }

    public static function for(string $id, callable $cb)
    {
        $prev = static::$id;
        static::$id = $id;

        try {
            return $cb();
        } finally {
            static::$id = $prev;
        }
    }
}
