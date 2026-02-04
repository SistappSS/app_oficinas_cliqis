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
        Schema::table('part_orders', function (Blueprint $table) {
            $table->string('supplier_email_used', 255)->nullable()->after('sent_at');
            $table->string('email_subject_used', 255)->nullable()->after('supplier_email_used');
            $table->longText('email_body_used')->nullable()->after('email_subject_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('part_orders', function (Blueprint $table) {
            $table->dropColumn(['supplier_email_used', 'email_subject_used', 'email_body_used']);
        });
    }
};
