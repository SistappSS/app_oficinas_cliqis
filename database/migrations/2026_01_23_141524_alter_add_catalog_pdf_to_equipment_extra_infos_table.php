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
        Schema::table('equipment_extra_infos', function (Blueprint $table) {
            $table->json('catalog_pdf')->nullable()->after('iframe_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_extra_infos', function (Blueprint $table) {
            $table->dropColumn('catalog_pdf');
        });
    }
};
