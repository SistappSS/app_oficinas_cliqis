<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('part_order_items', function (Blueprint $table) {
            $table->unsignedInteger('received_qty')->default(0)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('part_order_items', function (Blueprint $table) {
            $table->dropColumn('received_qty');
        });
    }
};
