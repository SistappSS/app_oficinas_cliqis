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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('customer_sistapp_id', 11)->index();

            $table->string('name');
            $table->string('cpfCnpj')->nullable();
            $table->string('mobilePhone')->nullable();

            $table->string('email')->nullable();

            $table->string('address')->nullable();
            $table->string('addressNumber')->nullable();
            $table->string('postalCode')->nullable();

            $table->string('cityName')->nullable();
            $table->string('state')->nullable();
            $table->string('province')->nullable();

            $table->string('complement')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
