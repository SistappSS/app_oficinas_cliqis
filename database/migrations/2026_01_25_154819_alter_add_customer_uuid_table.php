<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('service_order_invoices') || !Schema::hasColumn('service_order_invoices', 'customer_id')) {
            return;
        }

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

        DB::statement("ALTER TABLE `service_order_invoices` MODIFY `customer_id` CHAR(36) NULL");
    }

    public function down(): void
    {
        if (!Schema::hasTable('service_order_invoices') || !Schema::hasColumn('service_order_invoices', 'customer_id')) {
            return;
        }

        DB::statement("ALTER TABLE `service_order_invoices` MODIFY `customer_id` BIGINT UNSIGNED NULL");
    }
};
