<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_parts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('customer_sistapp_id', 36)->index();

            // link opcional com catálogo
            $table->uuid('part_id')->nullable();

            // chave de unificação do estoque
            $table->string('code', 64); // NOT NULL

            // snapshot útil pra tela
            $table->string('name', 255)->nullable();        // pode ser description
            $table->string('description', 255)->nullable();
            $table->string('ncm', 50)->nullable();

            // globais (soma de todos locais)
            $table->unsignedInteger('qty_on_hand_global')->default(0);
            $table->decimal('avg_cost_global', 12, 2)->default(0);
            $table->decimal('last_cost', 12, 2)->default(0);

            // venda opcional (se você quiser manter no produto)
            $table->decimal('default_sale_price', 12, 2)->default(0);
            $table->decimal('default_markup_percent', 6, 2)->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['customer_sistapp_id', 'code']);
            $table->index(['customer_sistapp_id', 'part_id']);
        });

        Schema::table('stock_parts', function (Blueprint $table) {
            $table->foreign('part_id')
                ->references('id')->on('parts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_parts');
    }
};
