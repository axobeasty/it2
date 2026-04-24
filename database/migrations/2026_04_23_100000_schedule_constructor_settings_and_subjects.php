<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_constructor_settings', function (Blueprint $table) {
            $table->id();
            $table->time('first_lesson_start');
            $table->unsignedSmallInteger('lesson_duration_minutes');
            $table->unsignedSmallInteger('break_minutes')->default(10);
            $table->unsignedTinyInteger('max_slots_per_day')->default(12);
            $table->timestamps();
        });

        DB::table('schedule_constructor_settings')->insert([
            'first_lesson_start' => '09:00:00',
            'lesson_duration_minutes' => 45,
            'break_minutes' => 10,
            'max_slots_per_day' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('schedule_subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('group_schedule_entries', function (Blueprint $table) {
            $table->unsignedTinyInteger('lesson_slot')->nullable()->after('weekday');
            $table->foreignId('schedule_subject_id')->nullable()->after('subject_title')->constrained('schedule_subjects')->nullOnDelete();
        });

        $now = now();
        $roleNames = ['Администратор', 'Заместитель директора', 'Директор', 'Преподаватель'];
        foreach ($roleNames as $roleName) {
            $roleId = DB::table('roles')->where('name', $roleName)->value('id');
            if ($roleId) {
                DB::table('role_page_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'page_key' => 'schedule_constructor_settings'],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::table('group_schedule_entries', function (Blueprint $table) {
            $table->dropForeign(['schedule_subject_id']);
            $table->dropColumn(['lesson_slot', 'schedule_subject_id']);
        });
        Schema::dropIfExists('schedule_subjects');
        Schema::dropIfExists('schedule_constructor_settings');
        DB::table('role_page_permissions')->where('page_key', 'schedule_constructor_settings')->delete();
    }
};
