<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class TenantSequence
{
    public static function next(string $key, ?string $tenantId = null): int
    {
        $tenantId = $tenantId ?? CustomerContext::get();
        if (!$tenantId) throw new \RuntimeException('Tenant ausente no contexto.');

        DB::statement("
            INSERT INTO tenant_sequences (customer_sistapp_id, `key`, `value`, created_at, updated_at)
            VALUES (?, ?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE `value` = LAST_INSERT_ID(`value` + 1), updated_at = NOW()
        ", [$tenantId, $key]);

        // <- pega exatamente o valor setado por LAST_INSERT_ID()
        return (int) DB::scalar('SELECT LAST_INSERT_ID()');
    }
}
