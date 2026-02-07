<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('customer_sistapp_id', 36)->index();

            $table->string('name', 120);
            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->unique(['customer_sistapp_id', 'name']);
            $table->index(['customer_sistapp_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_locations');
    }
};
