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
        Schema::create('part_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('customer_sistapp_id', 36)->index();

            // número tipo PP-0001 (único por tenant)
            $table->string('order_number', 20);
            $table->unique(['customer_sistapp_id', 'order_number']);

            // snapshot do pedido
            $table->string('title')->nullable();
            $table->string('billing_cnpj', 20)->nullable();
            $table->string('billing_uf', 2)->nullable();
            $table->date('order_date')->nullable();

            // draft, sent, open, pending, late, completed, cancelled (vamos ajustar depois)
            $table->string('status', 20)->default('draft');

            // taxas / totais
            $table->decimal('icms_rate', 6, 2)->default(0);
            $table->unsignedInteger('items_count')->default(0);

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('ipi_total', 12, 2)->default(0);
            $table->decimal('icms_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);

            $table->timestamp('sent_at')->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_orders');
    }
};
