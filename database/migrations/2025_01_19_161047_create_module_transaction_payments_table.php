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
        Schema::create('module_transaction_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('customer_sistapp_id', 11)->index();

            $table->foreignUuid('user_id')->constrained('customers')->index();

            $table->unsignedBigInteger('transaction_id');
            $table->foreign('transaction_id')->references('id')->on('module_transactions')->onDelete('cascade');

            $table->enum('cycle', ['monthly', 'annual'])->default('monthly');

            $table->date('paid_at');
            $table->date('expires_at');

            $table->index(['customer_sistapp_id', 'user_id']);

            $table->unique('transaction_id', 'uniq_mtp_tx');
            $table->index(['user_id','expires_at'], 'idx_mtp_user_expires');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_transaction_payments');
    }
};
