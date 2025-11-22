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
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->string('segment', 32);                  // 'agencia' | 'empresa' | 'freelancer'
            $table->boolean('is_required')->default(true);  // hoje sempre true, mas já deixa pronto
            $table->timestamps();

            $table->unique(['module_id', 'segment']);
            $table->index(['segment', 'is_required']);
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
