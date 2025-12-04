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
        Schema::create('account_payables', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('customer_sistapp_id', 25)->index();

            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            $table->string('description');
            $table->decimal('default_amount', 14, 2);

            $table->date('first_payment');
            $table->date('end_recurrence')->nullable();

            // Única/parcelada, mensal ou anual
            $table->enum('recurrence', ['yearly', 'monthly', 'variable']);

            // Para parcelamento (variable) ou para controle de recorrências
            $table->unsignedSmallInteger('times')->nullable();

            // Status da "conta mãe"
            $table->enum('status', ['open', 'closed', 'canceled'])->default('open');

            $table->index(['customer_sistapp_id', 'status']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_payables');
    }
};
