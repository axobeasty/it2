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
        Schema::create('stores', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->string('name');
            $table->string('inv_number')->nullable();
            $table->integer('count')->default(1);
            // Таблица inv__types создается следующей миграцией,
            // поэтому здесь не задаем FK, чтобы migrate:fresh на MySQL не падал.
            $table->unsignedBigInteger('inv_type_id');
            $table->boolean('is_enabled');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
