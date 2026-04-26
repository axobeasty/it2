<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_delivery_failures', function (Blueprint $table) {
            $table->id();
            $table->string('category', 32);
            $table->string('failure_code', 64);
            $table->string('subject');
            $table->string('recipient_email', 255)->default('');
            $table->unsignedBigInteger('recipient_employee_id')->nullable();
            $table->string('recipient_display', 255)->nullable();
            $table->unsignedBigInteger('triggered_by_employee_id')->nullable();
            $table->string('mail_type', 64)->nullable();
            $table->text('error_message');
            $table->text('phpmailer_error_info')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['category', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_delivery_failures');
    }
};
