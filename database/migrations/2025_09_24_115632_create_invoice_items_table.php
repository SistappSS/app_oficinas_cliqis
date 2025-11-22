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
        Schema::create('invoice_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id')->constrained()->cascadeOnDelete();

            $t->string('customer_sistapp_id', 64)->nullable();

            $t->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $t->string('description');
            $t->integer('qty')->default(1);
            $t->decimal('unit_amount',10,2);
            $t->enum('type',['one_time','subscription'])->default('one_time');

            $t->index('customer_sistapp_id', 'invoice_items_tenant_idx');

            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
