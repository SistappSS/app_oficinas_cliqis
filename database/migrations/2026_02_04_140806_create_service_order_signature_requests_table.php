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
        Schema::create('service_order_signature_links', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('service_order_id');
            $table->string('token', 128)->unique();

            $table->string('email')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('used_at')->nullable();

            $table->uuid('created_by_employee_id')->nullable();
            $table->timestamps();

            $table->foreign('service_order_id')
                ->references('id')->on('service_orders')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_order_signature_links');
    }
};
