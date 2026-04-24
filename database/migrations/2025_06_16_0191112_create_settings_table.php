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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('title')->default('Кгамт им. Л.Б. Васильева - IT Отдел');
            $table->integer('auth_mode')->default(0);// 0 - авторизация по паролю, 1 - авторизация по госуслугам, 2 - авторизация по госуслугам или через пароль
            $table->boolean('is_enabled')->default(false);
            $table->boolean('email_enabled')->default(true);
            $table->string('disable_reason')->default('Сайт в текущий момент находится на техническом обслуживании и в скором времени станет доступен!');
            $table->boolean('mode')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
