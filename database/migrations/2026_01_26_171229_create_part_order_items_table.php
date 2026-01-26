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
        Schema::create('part_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('customer_sistapp_id', 36)->index();

            $table->foreignUuid('part_order_id')
                ->constrained('part_orders')
                ->cascadeOnDelete();

            // se você já tem tabela parts (catálogo), linka aqui (opcional)
            $table->foreignUuid('part_id')
                ->nullable()
                ->constrained('parts')
                ->nullOnDelete();

            // snapshot do item (pra não depender do catálogo no futuro)
            $table->string('code')->nullable();
            $table->string('description')->nullable();
            $table->string('ncm')->nullable();

            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('ipi_rate', 6, 2)->default(0);
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('discount_rate', 6, 2)->default(0);

            // snapshot de cálculo (facilita view/pdf e auditoria)
            $table->decimal('line_subtotal', 12, 2)->default(0);
            $table->decimal('line_ipi_amount', 12, 2)->default(0);
            $table->decimal('line_discount_amount', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);

            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->index(['part_order_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_order_items');
    }
};
