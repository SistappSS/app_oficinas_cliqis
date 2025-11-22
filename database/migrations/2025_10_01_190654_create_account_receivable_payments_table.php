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
        Schema::create('account_receivable_payments', function (Blueprint $t) {
            $t->id();
            $t->string('customer_sistapp_id',25)->index();
            $t->foreignId('user_id')->constrained();
            $t->unsignedBigInteger('subscription_id')->nullable();
            $t->unsignedBigInteger('invoice_id')->nullable();

            $t->date('paid_at');
            $t->decimal('amount',14,2);
            $t->decimal('interest',14,2)->default(0);
            $t->decimal('fine',14,2)->default(0);
            $t->decimal('discount',14,2)->default(0);

            $t->string('reference')->nullable();
            $t->text('notes')->nullable();


            $t->timestamps();
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
