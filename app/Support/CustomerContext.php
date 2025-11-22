<?php

namespace App\Support;

final class CustomerContext
{
    private static ?string $id = null;
    private static bool $bypass = false;

    public static function set(?string $id): void { static::$id = $id; }
    public static function get(): ?string { return static::$id; }

    public static function bypass(bool $state = true): void { static::$bypass = $state; }
    public static function isBypassed(): bool { return static::$bypass; }

    /** Executa código com outro customer_sistapp_id temporário */
    public static function for(string $id, callable $cb) {
        $prev = static::$id;
        static::$id = $id;
        try { return $cb(); } finally { static::$id = $prev; }
    }
}
