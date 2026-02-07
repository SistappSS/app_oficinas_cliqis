<?php

namespace App\Services\Stock;

use App\Models\Stock\StockLocation;
use App\Models\Stock\StockMovementReason;
use App\Models\Stock\StockSetting;
use Illuminate\Support\Str;

class StockBootstrapper
{
    public static function ensure(string $tenantId): void
    {
        // default location
        $loc = StockLocation::where('customer_sistapp_id', $tenantId)
            ->where('is_default', true)
            ->first();

        if (!$loc) {
            $loc = StockLocation::create([
                'id' => (string) Str::uuid(),
                'customer_sistapp_id' => $tenantId,
                'name' => 'Principal',
                'is_default' => true,
            ]);
        }

        // settings
        $st = StockSetting::where('customer_sistapp_id', $tenantId)->first();
        if (!$st) {
            StockSetting::create([
                'id' => (string) Str::uuid(),
                'customer_sistapp_id' => $tenantId,
                'default_location_id' => $loc->id,
            ]);
        } elseif (!$st->default_location_id) {
            $st->default_location_id = $loc->id;
            $st->save();
        }

        // reasons system
        $defaults = [
            ['code' => 'receive_part_order', 'label' => 'Entrada via Pedido', 'is_system' => true],
            ['code' => 'manual_in',          'label' => 'Entrada Manual',    'is_system' => true],
            ['code' => 'manual_out',         'label' => 'Saída Manual',      'is_system' => true],
            ['code' => 'sale_out',           'label' => 'Saída por Venda',   'is_system' => true],
            ['code' => 'adjust',             'label' => 'Ajuste',            'is_system' => true],
        ];

        foreach ($defaults as $d) {
            StockMovementReason::firstOrCreate(
                ['customer_sistapp_id' => $tenantId, 'code' => $d['code']],
                ['id' => (string) Str::uuid(), 'label' => $d['label'], 'is_system' => $d['is_system'], 'is_active' => true]
            );
        }
    }
}
