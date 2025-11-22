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
//        Schema::table('budget_monthly_items', function (Blueprint $table) {
//            $table->unsignedInteger('discount_price');
//            $table->decimal('price_with_discount', 8, 2);
//        });

//        Schema::table('budgets', function (Blueprint $table) {
//            // remove UNIQUE antigo só em budget_code
//            $table->dropUnique('budgets_budget_code_unique');
//            // ou: $table->dropUnique(['budget_code']);
//
//            // cria UNIQUE por tenant (ajusta o nome se quiser)
//            $table->unique(
//                ['customer_sistapp_id', 'budget_code'],
//                'budgets_tenant_code_unique'
//            );
//        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
