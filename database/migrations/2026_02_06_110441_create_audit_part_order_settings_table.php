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
        Schema::create('audit_part_order_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('customer_sistapp_id', 36)->index();

            $table->uuid('actor_user_id')->nullable()->index();

            $table->string('action', 80)->index();

            $table->string('entity_type', 40)->nullable()->index();
            $table->string('entity_id', 36)->nullable()->index();

            $table->boolean('success')->nullable()->index();
            $table->json('meta')->nullable();

            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_part_order_settings');
    }
};
