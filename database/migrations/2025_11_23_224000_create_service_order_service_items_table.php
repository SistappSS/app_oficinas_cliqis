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
        Schema::create('service_order_service_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('customer_sistapp_id', 36)->index();

            $table->foreignUuid('service_order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('service_item_id')
                ->nullable()
                ->constrained('service_items')
                ->nullOnDelete();

            $table->foreignUuid('service_type_id')
                ->nullable()
                ->constrained('service_types')
                ->nullOnDelete();

            $table->string('description');
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_order_service_items');
    }
};
