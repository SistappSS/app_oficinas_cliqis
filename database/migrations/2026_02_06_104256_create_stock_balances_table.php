<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('customer_sistapp_id', 36)->index();

            $table->uuid('stock_part_id');
            $table->uuid('location_id');

            $table->unsignedInteger('qty_on_hand')->default(0);
            $table->decimal('avg_cost', 12, 2)->default(0);

            // limites (estoque baixo)
            $table->unsignedInteger('min_qty')->default(0);

            $table->timestamps();

            $table->unique('customer_sistapp_id');
            $table->unique('stock_part_id');
            $table->unique('location_id');

            $table->foreign('stock_part_id')->references('id')->on('stock_parts')->cascadeOnDelete();
            $table->foreign('location_id')->references('id')->on('stock_locations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
    }
};
