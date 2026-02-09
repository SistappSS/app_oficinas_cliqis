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
        Schema::create('payable_custom_fields', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('customer_sistapp_id', 36)->index();
            $table->uuid('created_by')->nullable()->index();

            $table->string('name', 80);
            $table->enum('type', ['deduct', 'add']); // Descontar / Acrescentar
            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->unique(['customer_sistapp_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payable_custom_fields');
    }
};
