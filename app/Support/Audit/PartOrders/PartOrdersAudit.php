<?php

namespace App\Support\Audit\PartOrders;

use App\Models\Audit\AuditPartOrderSetting;
use App\Support\TenantUser\CustomerContext;
use Illuminate\Support\Str;
use Throwable;

class PartOrdersAudit
{
    public static function log(
        string $action,
        ?string $entityType = null,
        ?string $entityId = null,
        ?bool $success = null,
        array $meta = []
    ): void {
        try {
            $tenant = (string) (CustomerContext::get() ?? '');
            if ($tenant === '') return; // sem tenant, não loga

            $user = auth()->user();

            AuditPartOrderSetting::create([
                'id' => (string) Str::uuid(),
                'customer_sistapp_id' => $tenant,
                'actor_user_id' => $user?->id,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'success' => $success,
                'meta' => empty($meta) ? null : $meta,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        } catch (Throwable $e) {
            // não derruba fluxo por causa de audit
            report($e);
        }
    }
}
