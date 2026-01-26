<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            // remove UNIQUE antigo
            $table->dropUnique('service_orders_order_number_unique');

            // cria UNIQUE por tenant
            $table->unique(['customer_sistapp_id', 'order_number'], 'so_tenant_order_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropUnique('so_tenant_order_unique');
            $table->unique('order_number');
        });
    }
};
