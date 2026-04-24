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
        Schema::create('inv_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('number');
            $table->date('date_in')->nullable();
            $table->date('date_out')->nullable();
            $table->string('room')->nullable();
            $table->foreignId('employees_id')->references('id')->on('employees')->default(1);
            $table->foreignId('store_id')->references('id')->on('stores');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inv_numbers');
    }
};
