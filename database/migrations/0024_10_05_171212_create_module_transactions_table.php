<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('module_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('customer_sistapp_id', 11)->index();

            $table->foreignUuid('user_id')->constrained('customers')->index();

            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');

            $table->json('module_ids')->nullable(false);

            $table->string('charge_id');
            $table->string('external_reference')->nullable();

            $table->json('selected_features')->nullable();

            $table->decimal('price_paid', 10, 2)->nullable();
            $table->json('price_breakdown')->nullable();

            $table->enum('billing_type', ['pix', 'credit_card']);

            $table->string('cycle', 20)->default('monthly');

            $table->date('due_date');
            $table->text('description');
            $table->string('status');

            $table->string('pix_url')->nullable();
            $table->longText('pix_qrcode')->nullable();

            $table->string('payment_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->string('remote_ip')->nullable();

            $table->unique('charge_id');

            $table->unique('charge_id', 'mt_charge_unique');

            $table->index('external_reference', 'mt_extref_idx');
            $table->index('processed_at', 'idx_mt_processed');
            $table->index(['user_id','status'], 'idx_mt_user_status');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_transactions');
    }
};
