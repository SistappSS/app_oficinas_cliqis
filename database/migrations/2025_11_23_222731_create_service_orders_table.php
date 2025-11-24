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
        Schema::create('service_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('customer_sistapp_id', 36)->index();

            $table->string('order_number')->unique();
            $table->date('order_date')->nullable();

            $table->string('status', 20)->default('draft');
            // draft, pending, approved, rejected, completed

            // cliente secundário (da OS)
            $table->foreignUuid('secondary_customer_id')
                ->nullable()
                ->constrained('secondary_customers')
                ->nullOnDelete();

            // técnicos / quem abriu
            $table->foreignUuid('technician_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            $table->foreignUuid('opened_by_employee_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            // snapshot de contato
            $table->string('requester_name')->nullable();
            $table->string('requester_email')->nullable();
            $table->string('requester_phone', 30)->nullable();
            $table->string('ticket_number')->nullable();

            // snapshot de endereço da OS
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('zip_code', 15)->nullable();

            // info de atendimento / pagamento
            $table->decimal('labor_hour_value', 10, 2)->default(0);
            $table->decimal('labor_total_hours', 8, 2)->default(0);
            $table->decimal('labor_total_amount', 10, 2)->default(0);

            $table->string('payment_condition')->nullable();
            $table->text('notes')->nullable();

            // totais
            $table->decimal('services_subtotal', 10, 2)->default(0);
            $table->decimal('parts_subtotal', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('addition_amount', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_orders');
    }
};
