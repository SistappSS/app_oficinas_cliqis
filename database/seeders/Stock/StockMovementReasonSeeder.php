<?php

namespace Database\Seeders\Stock;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockMovementReasonSeeder extends Seeder
{
    public function run(): void
    {
        // DEV only (não toca produção)
        if (!app()->environment(['local', 'development', 'testing'])) {
            return;
        }

        $tenantId = 'sist_000000';
        $now = now();

        $reasons = [
            ['code' => 'manual_in',     'label' => 'Entrada manual'],
            ['code' => 'manual_out',    'label' => 'Saída manual'],

            ['code' => 'purchase_in',   'label' => 'Entrada via pedido/compra'],
            ['code' => 'sale_out',      'label' => 'Saída via venda'],

            ['code' => 'adjust_in',     'label' => 'Ajuste de estoque (entrada)'],
            ['code' => 'adjust_out',    'label' => 'Ajuste de estoque (saída)'],

            ['code' => 'transfer_in',   'label' => 'Transferência (entrada)'],
            ['code' => 'transfer_out',  'label' => 'Transferência (saída)'],

            ['code' => 'return_in',     'label' => 'Devolução (entrada)'],
            ['code' => 'internal_out',  'label' => 'Consumo interno / brinde (saída)'],
        ];

        foreach ($reasons as $r) {
            $existingId = DB::table('stock_movement_reasons')
                ->where('customer_sistapp_id', $tenantId)
                ->where('code', $r['code'])
                ->value('id');

            if (!$existingId) {
                DB::table('stock_movement_reasons')->insert([
                    'id' => (string) Str::uuid(),
                    'customer_sistapp_id' => $tenantId,
                    'code' => $r['code'],
                    'label' => $r['label'],
                    'is_system' => 1,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('stock_movement_reasons')
                    ->where('id', $existingId)
                    ->update([
                        'label' => $r['label'],
                        'is_system' => 1,
                        'is_active' => 1,
                        'updated_at' => $now,
                    ]);
            }
        }
    }
}
