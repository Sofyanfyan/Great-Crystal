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
        Schema::create('expenditures', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['inside the school', 'out of school'])->default('inside the school');
            $table->string('description');
            $table->bigInteger('amount_spent');
            $table->dateTime('spent_at')->default(now());
            $table->timestamps();
            $table->softDeletes(); // Tambahkan kolom soft delete
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenditures');
    }
};
