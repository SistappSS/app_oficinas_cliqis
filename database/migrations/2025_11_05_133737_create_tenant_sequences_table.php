<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenant_sequences', function (Blueprint $table) {
            $table->id(); // PK (auto-increment) ok
            $table->string('customer_sistapp_id', 64);
            $table->string('key', 64);
            $table->unsignedBigInteger('value')->default(0);

            // ÚNICO para disparar o ON DUPLICATE KEY UPDATE
            $table->unique(['customer_sistapp_id', 'key'], 'tenant_sequences_tenant_key_unique');

            $table->timestamps();
        });


//        Schema::table('budgets', function (Blueprint $table) {
//            try {
//                $table->dropUnique('budgets_budget_code_unique');
//            } catch (\Throwable $e) {
//                try {
//                    $table->dropUnique(['budget_code']);
//                } catch (\Throwable $e) {
//                }
//            }
//            $table->unique(['customer_sistapp_id', 'budget_code'], 'budgets_tenant_code_unique');
//            $table->index(['customer_sistapp_id', 'budget_code'], 'budgets_tenant_code_idx');
//        });

// backfill
        $rows = DB::table('budgets')
            ->select('customer_sistapp_id', DB::raw('MAX(budget_code) as max_code'))
            ->groupBy('customer_sistapp_id')->get();

        foreach ($rows as $r) {
            DB::table('tenant_sequences')->updateOrInsert(
                ['customer_sistapp_id' => $r->customer_sistapp_id, 'key' => 'budget'],
                ['value' => (int)$r->max_code]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_sequences');
    }
};
