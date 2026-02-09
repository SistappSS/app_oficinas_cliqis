<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function indexExists(string $table, string $index): bool
    {
        $row = DB::selectOne("
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = ?
              AND index_name = ?
            LIMIT 1
        ", [$table, $index]);

        return (bool) $row;
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        }
    }

    private function addIndexIfMissing(string $table, string $index, string $sql): void
    {
        if (!$this->indexExists($table, $index)) {
            DB::statement($sql);
        }
    }

    public function up(): void
    {
        // ===== Precisions (sem depender de doctrine/dbal) =====
        DB::statement("ALTER TABLE `stock_balances` MODIFY `avg_cost` DECIMAL(12,4) NOT NULL DEFAULT 0.0000");

        DB::statement("ALTER TABLE `stock_parts` MODIFY `avg_cost_global` DECIMAL(12,4) NOT NULL DEFAULT 0.0000");
        DB::statement("ALTER TABLE `stock_parts` MODIFY `last_cost` DECIMAL(12,4) NOT NULL DEFAULT 0.0000");

        DB::statement("ALTER TABLE `stock_movement_items` MODIFY `unit_cost` DECIMAL(12,4) NOT NULL DEFAULT 0.0000");
        // mantém moeda em 2 casas no total (igual seu schema atual)
        DB::statement("ALTER TABLE `stock_movement_items` MODIFY `total_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00");

        // ===== Unique correto em stock_balances =====
        // remove os uniques antigos (se existirem)
        $this->dropIndexIfExists('stock_balances', 'stock_balances_customer_sistapp_id_unique');
        $this->dropIndexIfExists('stock_balances', 'stock_balances_location_id_unique');
        $this->dropIndexIfExists('stock_balances', 'stock_balances_stock_part_id_unique');

        // garante o unique composto
        $this->addIndexIfMissing(
            'stock_balances',
            'stock_balances_tenant_part_location_unique',
            "ALTER TABLE `stock_balances`
             ADD UNIQUE KEY `stock_balances_tenant_part_location_unique` (`customer_sistapp_id`, `stock_part_id`, `location_id`)"
        );

        // ===== Índices extras (performance: filtros por período / whereExists / joins) =====
        $this->addIndexIfMissing(
            'stock_movements',
            'stock_movements_tenant_created_at_index',
            "ALTER TABLE `stock_movements`
             ADD INDEX `stock_movements_tenant_created_at_index` (`customer_sistapp_id`, `created_at`)"
        );

        $this->addIndexIfMissing(
            'stock_movement_items',
            'stock_movement_items_tenant_movement_index',
            "ALTER TABLE `stock_movement_items`
             ADD INDEX `stock_movement_items_tenant_movement_index` (`customer_sistapp_id`, `movement_id`)"
        );

        // cobre filtro por stock_part_id dentro do whereExists (movement_id + stock_part_id)
        $this->addIndexIfMissing(
            'stock_movement_items',
            'stock_movement_items_tenant_part_movement_index',
            "ALTER TABLE `stock_movement_items`
             ADD INDEX `stock_movement_items_tenant_part_movement_index` (`customer_sistapp_id`, `stock_part_id`, `movement_id`)"
        );
    }

    public function down(): void
    {
        // down conservador: remove só os índices adicionados por essa migration
        $this->dropIndexIfExists('stock_movements', 'stock_movements_tenant_created_at_index');
        $this->dropIndexIfExists('stock_movement_items', 'stock_movement_items_tenant_movement_index');
        $this->dropIndexIfExists('stock_movement_items', 'stock_movement_items_tenant_part_movement_index');

        // não vou tentar “voltar” uniques antigos pra não quebrar ambiente com dados já corretos
        // nem desfazer precisões (isso também pode dar dor de cabeça com dados).
    }
};
