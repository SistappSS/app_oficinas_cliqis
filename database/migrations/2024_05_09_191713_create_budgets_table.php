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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();

            $table->string('customer_sistapp_id', 25)->index();

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            $table->unsignedInteger('budget_code')->unique();

            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers');

            $table->string('customer_email')->nullable();

            $table->enum('status', ['pending','approved','rejected'])->default('pending');

            $table->timestamp('approved_at')->nullable();

            $table->string('payment_date');
            $table->date('signal_date')->nullable();

            $table->string('deadline');
            $table->string('signal');

            $table->decimal('signal_price', 8, 2);

            $table->decimal('remaining_price', 8, 2);
            $table->decimal('total_budget_price', 8, 2);

            $table->decimal('discount_percent',5,2)->default(0);

            $table->enum('discount_scope',['all','one'])->default('all');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
