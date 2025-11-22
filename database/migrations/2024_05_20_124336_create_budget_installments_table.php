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
        Schema::create('budget_installments', function (Blueprint $table) {
            $table->id();

            $table->string('customer_sistapp_id', 25)->index();

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            $table->unsignedBigInteger('budget_id')->nullable()->index();

            $table->unsignedBigInteger('budget_item_id');
            $table->foreign('budget_item_id')->references('id')->on('budget_items');

            $table->unsignedInteger('installment_number');
            $table->string('payment_date');
            $table->decimal('price', 8, 2);
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_installments');
    }
};
