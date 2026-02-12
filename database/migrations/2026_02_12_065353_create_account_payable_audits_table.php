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
        Schema::create('account_payable_audits', function (Blueprint $t) {
            $t->uuid('id')->primary();

            $t->string('customer_sistapp_id', 25)->index();
            $t->uuid('user_id')->index();

            $t->string('entity', 40)->index();
            $t->uuid('entity_id')->nullable()->index();

            $t->string('action', 40)->index();

            $t->json('before')->nullable();
            $t->json('after')->nullable();

            $t->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_payable_audits');
    }
};
