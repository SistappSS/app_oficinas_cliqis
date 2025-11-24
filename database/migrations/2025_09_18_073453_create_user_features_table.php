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
        Schema::create('user_features', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->unsignedBigInteger('user_module_permission_id');
            $table->foreign('user_module_permission_id')->references('id')->on('user_module_permissions')->onDelete('cascade');

            $table->unsignedBigInteger('feature_id');
            $table->foreign('feature_id')->references('id')->on('features')->onDelete('cascade');

            $table->boolean('is_active')->default(true);

            $table->decimal('price', 8, 2)->default(0);
            $table->boolean('selected')->default(true);

            $table->dateTime('activated_at')->nullable();
            $table->decimal('prorated_amount', 10, 2)->nullable();

            $table->timestamp('expires_at')->nullable()->index();

            $table->unique(['user_module_permission_id', 'feature_id'], 'uniq_user_feature');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_features');
    }
};
