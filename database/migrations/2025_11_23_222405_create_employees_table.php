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
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('customer_sistapp_id', 36)->index();

            $table->foreignUuid('user_id')->constrained('users')->index();

            $table->foreignUuid('department_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('document_number', 20)->nullable();
            $table->string('position')->nullable();

            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->boolean('is_technician')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
