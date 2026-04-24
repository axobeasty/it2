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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('file_path')->nullable();
            $table->string('description')->nullable()->default('Нет');
            $table->foreignId('category_id')->references('id')->on('o__categories')->nullOnDelete();
            $table->foreignId('employee_id')->references('id')->on('employees')->nullOnDelete();
            $table->integer('status')->default(0);
            $table->string('room')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
