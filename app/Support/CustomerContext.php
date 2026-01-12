<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        if (static::$id !== null) return static::$id;
        if (static::$bypass) return null;

        $user = Auth::user();
        if (! $user) return null;

        // 1) OWNER
        $login = $user->customerLogin ?? null;
        if ($login && $login->customer_sistapp_id) {
            static::$id = $login->customer_sistapp_id;
            return static::$id;
        }

        // 2) EMPLOYEE (sem Eloquent pra não disparar scopes/boot)
        $tenantId = DB::table('customer_employee_users')
            ->where('user_id', $user->id)
            ->value('customer_sistapp_id');

        if ($tenantId) {
            static::$id = $tenantId;
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

        try { return $cb(); }
        finally { static::$id = $prev; }
    }
}
