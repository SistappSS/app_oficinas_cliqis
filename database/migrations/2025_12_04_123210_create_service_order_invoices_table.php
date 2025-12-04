<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_order_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // se suas service_orders usam bigIncrements, troque para unsignedBigInteger
            $table->uuid('service_order_id');

            // número da NF / fatura (pra futuro)
            $table->string('number')->nullable();

            // valor faturado (normalmente igual ao grand_total da OS)
            $table->decimal('amount', 12, 2);

            // aqui estou seguindo seu texto "data de pagamento"
            // se quiser futuro "vencimento", você cria outro campo
            $table->date('payment_date');

            // pix / boleto / cartao_credito / dinheiro...
            $table->string('payment_method', 50);

            // quantidade de parcelas
            $table->unsignedInteger('installments')->default(1);

            // open | paid | canceled (por enquanto sempre "open")
            $table->string('status', 20)->default('open');

            $table->timestamps();

            $table->foreign('service_order_id')
                ->references('id')
                ->on('service_orders')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_order_invoices');
    }
};
