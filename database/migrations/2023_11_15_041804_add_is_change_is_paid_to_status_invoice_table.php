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
        Schema::table('status_invoice_mails', function (Blueprint $table) {
            $table->integer('is_paid')->default(false);
            $table->integer('is_change')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('status_invoice_mails', function (Blueprint $table) {
            $table->dropColumn('is_paid');
            $table->dropColumn('is_change');
        });
    }
};
