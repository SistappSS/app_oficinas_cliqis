<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_movement_reasons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('customer_sistapp_id', 36)->index();

            $table->string('code', 80); // ex: receive_part_order, manual_in, sale_out
            $table->string('label', 120);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['customer_sistapp_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movement_reasons');
    }
};
