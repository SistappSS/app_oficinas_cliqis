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
        Schema::table('part_orders', function (Blueprint $table) {
            $table->string('payment_mode', 20)
                ->default('avista')
                ->after('grand_total');

            // sinal
            $table->decimal('signal_amount', 12, 2)
                ->default(0.00)
                ->after('payment_mode');

            $table->date('signal_due_date')
                ->nullable()
                ->after('signal_amount');

            // parcelas
            $table->unsignedSmallInteger('installments_count')
                ->default(0)
                ->after('signal_due_date');

            $table->date('installments_first_due_date')
                ->nullable()
                ->after('installments_count');

            // vínculo com contas a pagar (idempotência)
            $table->uuid('account_payable_id')
                ->nullable()
                ->after('installments_first_due_date');

            $table->index('account_payable_id', 'part_orders_account_payable_id_idx');

            $table->foreign('account_payable_id', 'part_orders_account_payable_id_fk')
                ->references('id')
                ->on('account_payables')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('part_orders', function (Blueprint $table) {
            $table->dropForeign('part_orders_account_payable_id_fk');
            $table->dropIndex('part_orders_account_payable_id_idx');

            $table->dropColumn([
                'payment_mode',
                'signal_amount',
                'signal_due_date',
                'installments_count',
                'installments_first_due_date',
                'account_payable_id',
            ]);
        });
    }
};
