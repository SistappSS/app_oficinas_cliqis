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
        Schema::create('account_receivables', function (Blueprint $t) {
            $t->id();
            $t->string('customer_sistapp_id',25)->index();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->unsignedBigInteger('budget_id')->nullable()->index();
            $t->foreign('budget_id')->references('id')->on('budgets')->nullOnDelete();

            $t->string('description');
            $t->text('observation')->nullable();

            $t->date('first_payment');
            $t->enum('recurrence',['yearly','monthly','variable'])->default('variable');
            $t->date('end_recurrence')->nullable();

            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_receivables');
    }
};
