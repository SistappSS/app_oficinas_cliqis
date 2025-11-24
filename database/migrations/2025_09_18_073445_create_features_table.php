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
        Schema::create('features', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('module_id');
            $table->foreign('module_id', 'fk_module_segment_requirements_module')
                ->references('id')
                ->on('modules');

            $table->string('name');
            $table->decimal('price', 8, 2);
            $table->json('roles'); // array de roles habilitadas (pode ser json_encode(['financeiro.view', 'financeiro.edit']))
            $table->boolean('is_active')->default(true);
            $table->boolean('is_required')->default(false);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('features');
    }
};
