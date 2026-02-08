<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ---- stock_balances: conserta uniques + precisão
        Schema::table('stock_balances', function (Blueprint $table) {
            // dropa os uniques errados (nomes iguais ao seu SQL)
            //$table->dropUnique('stock_balances_customer_sistapp_id_unique');
            //$table->dropUnique('stock_balances_location_id_unique');
            //$table->dropUnique('stock_balances_stock_part_id_unique');
        });

        // precisão custo médio
        DB::statement("ALTER TABLE stock_balances MODIFY avg_cost DECIMAL(12,4) NOT NULL DEFAULT 0.0000");

        Schema::table('stock_balances', function (Blueprint $table) {
            $table->unique(
                ['customer_sistapp_id', 'stock_part_id', 'location_id'],
                'stock_balances_tenant_part_location_unique'
            );
        });

        // ---- stock_parts: custo global com precisão
        DB::statement("ALTER TABLE stock_parts MODIFY avg_cost_global DECIMAL(12,4) NOT NULL DEFAULT 0.0000");
        DB::statement("ALTER TABLE stock_parts MODIFY last_cost DECIMAL(12,4) NOT NULL DEFAULT 0.0000");

        // ---- stock_movement_items: custo unit com precisão
        DB::statement("ALTER TABLE stock_movement_items MODIFY unit_cost DECIMAL(12,4) NOT NULL DEFAULT 0.0000");

        // ---- part_order_items: quantidade tem que ser inteiro (você decidiu isso)
        // ATENÇÃO: só rode isso se NÃO existir quantidade fracionada já gravada.
        DB::statement("ALTER TABLE part_order_items MODIFY quantity INT UNSIGNED NOT NULL DEFAULT 1");
    }

    public function down(): void
    {
        // se precisar voltar, você recria como estava (não recomendo).
    }
};
