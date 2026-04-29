<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_password_resets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('token', 64)->comment('SHA-256 hex of secret from link');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('token');
            $table->index(['employee_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_password_resets');
    }
};
