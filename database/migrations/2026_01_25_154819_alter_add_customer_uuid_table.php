<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('service_order_invoices') || !Schema::hasColumn('service_order_invoices', 'customer_id')) {
            return;
        }

        // 1) Derruba FK se existir (descobre o nome no information_schema)
        $fk = DB::selectOne("
            SELECT CONSTRAINT_NAME AS name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'service_order_invoices'
              AND COLUMN_NAME = 'customer_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ");

        if ($fk && !empty($fk->name)) {
            DB::statement("ALTER TABLE `service_order_invoices` DROP FOREIGN KEY `{$fk->name}`");
        }

        // 2) Altera o tipo para UUID (CHAR(36))
        DB::statement("ALTER TABLE `service_order_invoices` MODIFY `customer_id` CHAR(36) NULL");
    }

    public function down(): void
    {
        if (!Schema::hasTable('service_order_invoices') || !Schema::hasColumn('service_order_invoices', 'customer_id')) {
            return;
        }

        // Volta para BIGINT UNSIGNED (como era)
        DB::statement("ALTER TABLE `service_order_invoices` MODIFY `customer_id` BIGINT UNSIGNED NULL");

        // Se você tinha FK antes e quiser recriar no rollback, faça aqui manualmente:
        // DB::statement("ALTER TABLE `service_order_invoices`
        //   ADD CONSTRAINT `soi_customer_id_foreign`
        //   FOREIGN KEY (`customer_id`) REFERENCES `SUA_TABELA`(`id`) ON DELETE SET NULL");
    }
};
