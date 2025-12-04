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
        Schema::create('account_receivable_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('customer_sistapp_id', 25)->index();

            $table->unsignedBigInteger('invoice_id')->index();
            $table->foreign('invoice_id')->references('id')->on('service_order_invoices');

            $table->date('paid_at');
            $table->decimal('amount', 14, 2);
            $table->decimal('interest', 14, 2)->default(0);
            $table->decimal('fine', 14, 2)->default(0);
            $table->decimal('discount', 14, 2)->default(0);

            $table->string('reference')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_receivable_payments');
    }
};
