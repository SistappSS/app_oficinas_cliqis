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
        Schema::create('user_module_controls', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->unsignedBigInteger('user_module_permission_id');
            $table->foreign('user_module_permission_id')->references('id')->on('user_module_permissions');

            $table->string('cycle');
            $table->decimal('total');

            $table->date('contracted_date');
            $table->unsignedTinyInteger('month_reference');
            $table->unsignedSmallInteger('year_reference');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_module_controls');
    }
};
