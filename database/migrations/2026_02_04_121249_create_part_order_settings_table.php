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
        Schema::create('part_order_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // tenant
            $table->string('customer_sistapp_id', 36)->index();
            $table->unique('customer_sistapp_id');

            $table->foreignUuid('default_supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete();

            $table->string('billing_cnpj', 20)->nullable();
            $table->string('billing_uf', 2)->nullable();

            // templates de e-mail
            $table->string('email_subject_tpl', 200)->nullable();
            $table->text('email_body_tpl')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_order_settings');
    }
};
