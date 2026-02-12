<?php

namespace App\Traits;

use App\Support\TenantUser\CustomerContext;

trait HasCustomerScope
{
    protected static function bootHasCustomerScope(): void
    {
        static::addGlobalScope('customer', function ($builder) {
            if (CustomerContext::isBypassed()) return; // deixe sempre false em prod

            $id = CustomerContext::get();
            if (!$id) { $builder->whereRaw('1=0'); return; }

            $table = $builder->getModel()->getTable();
            $col   = property_exists($builder->getModel(), 'tenantColumn')
                ? $builder->getModel()->tenantColumn
                : 'customer_sistapp_id';

            $builder->where("$table.$col", $id);
        });

        static::creating(function ($model) {
            if (CustomerContext::isBypassed()) return;
            if (empty($model->customer_sistapp_id)) {
                $model->customer_sistapp_id = CustomerContext::get();
            }
        });

        // opcional: impedir troca de tenant em update
        static::updating(function ($model) {
            if ($model->isDirty('customer_sistapp_id')) {
                throw new \LogicException('Proibido alterar tenant do registro.');
            }
        });
    }
}
