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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->foreign('student_id')->references('id')->on('students');
            $table->string('subject')->nullable();
            $table->string('description')->nullable();
            $table->bigInteger('amount');
            $table->boolean('paidOf')->default(false);
            $table->integer('discount')->nullable()->default(0);
            $table->date('deadline_invoice')->default(date('Y-m-t'));
            $table->integer('installment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};