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
        Schema::create('account_payable_recurrences', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('customer_sistapp_id', 25)->index();

            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            $table->foreignUuid('account_payable_id')
                ->constrained('account_payables')
                ->cascadeOnDelete();

            // Número da recorrência / parcela (1, 2, 3...)
            $table->unsignedSmallInteger('recurrence_number');

            $table->date('due_date');
            $table->decimal('amount', 14, 2);

            $table->enum('status', ['pending', 'paid', 'canceled'])->default('pending');

            // Somatório dos pagamentos realizados
            $table->decimal('amount_paid', 14, 2)->default(0);

            // Caso esteja 100% paga, data de pagamento final
            $table->date('paid_at')->nullable();

            $table->index(['customer_sistapp_id', 'status']);
            $table->index(['account_payable_id', 'due_date']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_payable_recurrences');
    }
};
