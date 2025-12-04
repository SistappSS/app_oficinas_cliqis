<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_order_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('customer_sistapp_id', 25)->index();

            $table->unsignedBigInteger('service_order_id')->index();
            $table->foreign('service_order_id')->references('id')->on('service_orders')->cascadeOnDelete();

            // cliente da OS (se já tiver tabela customers/clients, ajusta aqui)
            $table->unsignedBigInteger('customer_id')->nullable()->index();

            // número da fatura (ex: #000123)
            $table->string('number')->unique();

            // data de vencimento dessa parcela específica
            $table->date('due_date');

            // valor dessa parcela (sinal ou parcela do restante)
            $table->decimal('amount', 14, 2);

            // info de parcelamento
            // ex: sinal -> installment = 0, installments_total = total de parcelas (incluindo sinal)
            // ex: parcela 1/3 -> installment = 1, installments_total = 3
            $table->unsignedInteger('installment')->default(1);
            $table->unsignedInteger('installments_total')->default(1);

            // tipo dessa cobrança
            $table->enum('type', ['single', 'signal', 'parcel'])->default('single');

            // status financeiro
            $table->enum('status', ['pending', 'overdue', 'paid', 'canceled'])->default('pending');

            // opcional: forma de pagamento escolhida na geração (dinheiro, pix, cartão, etc)
            $table->string('payment_method')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_order_invoices');
    }
};
