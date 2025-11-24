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
        Schema::create('user_module_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('customer_sistapp_id', 11)->index();

            //$table->foreignUuid('user_id')->constrained('users')->index();

            $table->unsignedBigInteger('module_id');
            $table->foreign('module_id')->references('id')->on('modules')->onDelete('cascade');

            $table->dateTime('expires_at')->nullable();

            $table->unique(['user_id', 'module_id']);
            $table->unique(['user_id','module_id','customer_sistapp_id'], 'ump_user_module_tenant_unique');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_module_permissions');
    }
};
