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
        Schema::create('additional_customer_infos', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('customer_sistapp_id', 11)->index();

            $table->uuid('user_id');
            $table->foreign('user_id', 'fk_additional_customer_infos_user')
                ->references('id')
                ->on('users');

            $table->string('website_url')->nullable();
            $table->string('segment')->nullable();

            $table->unique('user_id', 'aci_user_unique');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('additional_customer_infos');
    }
};
