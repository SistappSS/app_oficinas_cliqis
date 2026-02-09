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
        Schema::create('stock_part_price_logs', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('customer_sistapp_id', 36)->index();
            $table->char('stock_part_id', 36)->index();
            $table->char('location_id', 36)->nullable()->index();
            $table->char('user_id', 36)->nullable()->index();

            $table->string('field', 40); // sale_price | markup_percent | last_cost | avg_cost | min_qty
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_part_price_logs');
    }
};
