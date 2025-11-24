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

            $table->uuid('user_id');
            $table->foreign('user_id', 'fk_module_transactions_user')
                ->references('id')
                ->on('users');

            $table->uuid('module_id');
            $table->foreign('module_id', 'fk_user_module_permissions_module')
                ->references('id')
                ->on('modules');

            $table->dateTime('expires_at')->nullable();

            //$table->unique(['user_id','module_id','customer_sistapp_id'], 'ump_user_module_tenant_unique');

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
