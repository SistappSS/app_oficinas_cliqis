<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('customer_sistapp_id', 36)->index();

            $table->string('type', 10); // in | out | adjust
            $table->uuid('reason_id')->nullable();

            // origem (pedido, venda, manual...)
            $table->string('source_type', 50)->nullable(); // part_order, sale_order, manual
            $table->uuid('source_id')->nullable();

            $table->uuid('user_id')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['customer_sistapp_id', 'type']);
            $table->index(['customer_sistapp_id', 'source_type', 'source_id']);

            $table->foreign('reason_id')->references('id')->on('stock_movement_reasons')->nullOnDelete();
        });

        Schema::create('stock_movement_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('customer_sistapp_id', 36)->index();

            $table->uuid('movement_id');
            $table->uuid('stock_part_id');
            $table->uuid('location_id');

            $table->string('code', 64); // snapshot
            $table->string('description', 255)->nullable();
            $table->string('ncm', 50)->nullable();

            $table->unsignedInteger('qty'); // inteiro

            // custo do recebimento/saída
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);

            // venda opcional
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->decimal('markup_percent', 6, 2)->default(0);

            $table->timestamps();

            $table->foreign('movement_id')->references('id')->on('stock_movements')->cascadeOnDelete();
            $table->foreign('stock_part_id')->references('id')->on('stock_parts')->cascadeOnDelete();
            $table->foreign('location_id')->references('id')->on('stock_locations')->cascadeOnDelete();

            $table->index(['customer_sistapp_id', 'stock_part_id']);
            $table->index(['customer_sistapp_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movement_items');
        Schema::dropIfExists('stock_movements');
    }
};
