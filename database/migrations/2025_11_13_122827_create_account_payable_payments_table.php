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
        Schema::create('account_payable_payments', function (Blueprint $table) {
            $table->id();

            $table->string('customer_sistapp_id', 25)->index();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->foreignId('payable_recurrence_id')
                ->constrained('account_payable_recurrences')
                ->cascadeOnDelete();

            $table->date('paid_at');
            $table->decimal('amount', 14, 2); // valor pago (pode ser parcial)

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_payable_payments');
    }
};
