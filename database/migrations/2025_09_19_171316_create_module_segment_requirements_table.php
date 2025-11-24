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
        Schema::create('module_segment_requirements', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('module_id');
            $table->foreign('module_id', 'fk_module_segment_module')
                ->references('id')
                ->on('modules');

            $table->string('segment', 32);                  // 'agencia' | 'empresa' | 'freelancer'
            $table->boolean('is_required')->default(true);  // hoje sempre true, mas já deixa pronto
            $table->timestamps();

            //$table->unique(['module_id', 'segment']);
            //$table->index(['segment', 'is_required']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_segment_requirements');
    }
};
