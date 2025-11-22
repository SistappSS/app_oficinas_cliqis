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
        Schema::create('yearly_transactions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('budget_yearly_item_id');
            $table->foreign('budget_yearly_item_id')->references('id')->on('budget_yearly_items')->onDelete('cascade');

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            $table->string('customer_sistapp_id', 25)->index();

            $table->integer('reference_month');

            $table->integer('reference_year');

            $table->decimal('amount', 10, 2);

            $table->enum('status', ['paid', 'pending'])->default('pending');

            $table->date('payment_date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('yearly_transactions');
    }
};
