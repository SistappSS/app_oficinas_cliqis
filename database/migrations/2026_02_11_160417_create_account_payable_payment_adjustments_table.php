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
        Schema::create('account_payable_payment_adjustments', function (Blueprint $t) {
            $t->uuid('id')->primary();

            $t->string('customer_sistapp_id', 25)->index();
            $t->uuid('user_id')->index();

            $t->uuid('payable_recurrence_id')->index();
            $t->uuid('payment_id')->index();

            $t->uuid('custom_field_id')->index();

            $t->string('type_snapshot', 10); // deduct|add
            $t->decimal('value', 14, 2);     // sempre positivo

            $t->timestamps();

            $t->index(['customer_sistapp_id', 'payable_recurrence_id'], 'ap_adj_tenant_rec_idx');

            $t->foreign('payable_recurrence_id', 'ap_adj_rec_fk')
                ->references('id')->on('account_payable_recurrences')
                ->cascadeOnDelete();

            $t->foreign('payment_id', 'ap_adj_payment_fk')
                ->references('id')->on('account_payable_payments')
                ->cascadeOnDelete();

            $t->foreign('custom_field_id', 'ap_adj_field_fk')
                ->references('id')->on('payable_custom_fields')
                ->restrictOnDelete();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_payable_payment_adjustments');
    }
};
