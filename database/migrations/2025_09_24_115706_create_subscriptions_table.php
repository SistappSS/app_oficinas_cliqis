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
        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id();
            $t->string('customer_sistapp_id',25)->index();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('budget_id')->nullable()->constrained('budgets')->nullOnDelete();
            $t->foreignId('customer_id')->constrained('customers');

            // vincula ao item mensal/anual original (se quiser rastrear)
            $t->foreignId('budget_monthly_item_id')->nullable()->constrained('budget_monthly_items')->nullOnDelete();
            $t->foreignId('budget_yearly_item_id')->nullable()->constrained('budget_yearly_items')->nullOnDelete();

            $t->string('name');
            $t->decimal('amount',10,2);
            $t->enum('period',['monthly','yearly']);
            $t->unsignedTinyInteger('day_of_month')->nullable();  // monthly
            $t->unsignedTinyInteger('month_of_year')->nullable(); // yearly (1..12)
            $t->date('next_due_date');
            $t->boolean('auto_reminder')->default(true);
            $t->boolean('active')->default(true);

            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
