<?php

namespace App\Support\Audit\Payables;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayablesAudit
{
    public static function log(
        string $tenantId,
        string $userId,
        string $entity,
        ?string $entityId,
        string $action,
        $before = null,
        $after = null
    ): void {
        DB::table('account_payable_audits')->insert([
            'id' => (string) Str::uuid(),
            'customer_sistapp_id' => $tenantId,
            'user_id' => $userId,
            'entity' => $entity,
            'entity_id' => $entityId,
            'action' => $action,
            'before' => $before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
            'after'  => $after  ? json_encode($after,  JSON_UNESCAPED_UNICODE) : null,
            'created_at' => now(),
        ]);
    }
}
