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
        Schema::create('budget_configs', function (Blueprint $table) {
            $table->id();

            $table->string('customer_sistapp_id')->unique()->index();

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            $table->json('org');            // {name, document, email, phone, city, state, country, address, number, complement, zip, website}
            $table->json('representative')->nullable(); // {name, document, email, phone, city, state, country, address, number, complement, zip}
            $table->json('texts')->nullable();          // {services, payment} (HTML permitido)
            $table->json('logo')->nullable();           // {data, mime, max_height}

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_configs');
    }
};
