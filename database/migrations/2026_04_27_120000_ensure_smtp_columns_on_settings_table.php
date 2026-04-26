<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Подстраховка для окружений, где миграция 2026_04_26_100000 ещё не выполнялась
 * (например, деплой без php artisan migrate). Колонки добавляются только если отсутствуют.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        if (! Schema::hasColumn('settings', 'smtp_host')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('smtp_host')->nullable();
            });
        }
        if (! Schema::hasColumn('settings', 'smtp_port')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->unsignedSmallInteger('smtp_port')->nullable();
            });
        }
        if (! Schema::hasColumn('settings', 'smtp_encryption')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('smtp_encryption', 16)->nullable();
            });
        }
        if (! Schema::hasColumn('settings', 'smtp_username')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('smtp_username')->nullable();
            });
        }
        if (! Schema::hasColumn('settings', 'smtp_password')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->text('smtp_password')->nullable();
            });
        }
        if (! Schema::hasColumn('settings', 'mail_from_address')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('mail_from_address')->nullable();
            });
        }
        if (! Schema::hasColumn('settings', 'mail_from_name')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('mail_from_name')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Не удаляем колонки: они могли быть созданы другой миграцией.
    }
};
