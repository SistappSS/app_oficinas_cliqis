<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('customer_sistapp_id', 36)->unique();

            $table->uuid('default_location_id')->nullable();

            $table->timestamps();

            $table->foreign('default_location_id')
                ->references('id')->on('stock_locations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_settings');
    }
};
