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
        Schema::create('completed_service_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('customer_sistapp_id', 36)->index();

            $table->foreignUuid('service_order_id')
                ->constrained()
                ->cascadeOnDelete();

            // assinatura cliente secundário
            $table->string('client_name')->nullable();
            $table->string('client_email')->nullable();
            $table->string('client_signature_path')->nullable();
            $table->dateTime('client_signed_at')->nullable();

            // assinatura técnico
            $table->foreignUuid('technician_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            $table->string('technician_signature_path')->nullable();
            $table->dateTime('technician_signed_at')->nullable();

            $table->dateTime('completed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('completed_service_orders');
    }
};
