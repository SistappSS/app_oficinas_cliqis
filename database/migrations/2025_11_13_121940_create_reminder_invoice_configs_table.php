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
        Schema::create('reminder_invoice_configs', function (Blueprint $t) {
            $t->id();

            // multitenant
            $t->string('customer_sistapp_id', 25)->index();

            // último usuário que editou (opcional, mas útil)
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // qual momento do lembrete
            $t->enum('trigger', [
                'before_3_days',   // 3 dias antes
                'on_due_date',     // no dia do vencimento
                'after_1_day',     // 1 dia depois
                'manual',          // envio manual pelo botão
            ]);

            // só pra exibir na UI, ex: "3 dias antes", "No vencimento"
            $t->string('name');

            // assunto do e-mail
            $t->string('subject');

            // corpo do e-mail com placeholders ({{customer_name}}, {{due_date}}, etc)
            $t->longText('body');

            // se essa config está ativa
            $t->boolean('is_active')->default(true);

            $t->timestamps();

            // garante 1 config por trigger por cliente
            $t->unique(['customer_sistapp_id', 'trigger']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminder_invoice_configs');
    }
};
