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
        Schema::create('equipment_extra_infos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('customer_sistapp_id', 36)->index();

            $table->uuid('equipment_id');
            $table->foreign('equipment_id', 'fk_equipment_extra_infos_equipment')
                ->references('id')
                ->on('equipments');

            $table->json('image_path')->nullable();
            $table->text('iframe_url')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_extra_infos');
    }
};
