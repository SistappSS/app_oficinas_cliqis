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
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->string('customer_sistapp_id',25)->index();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('budget_id')->nullable()->constrained('budgets')->nullOnDelete();
            $t->uuid('budget_installment_id')->nullable();
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            $t->string('number')->unique();
            $t->date('due_date');
            $t->decimal('amount',10,2);
            $t->unsignedInteger('installments')->default(1);
            $t->boolean('is_recurring')->default(false);
            $t->enum('recurring_period',['monthly','yearly'])->nullable();
            $t->boolean('auto_reminder')->default(true);

            $t->enum('status',['pending','paid','overdue','canceled'])->default('pending');
            $t->unsignedInteger('sent_count')->default(0);
            $t->timestamp('last_sent_at')->nullable();

            $t->unique(['customer_sistapp_id','number'], 'invoices_tenant_number_unique');
            $t->unique(['customer_sistapp_id','budget_installment_id'], 'inv_tenant_inst_unique');

            $t->index(['customer_sistapp_id','number'], 'invoices_tenant_number_idx');

            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
