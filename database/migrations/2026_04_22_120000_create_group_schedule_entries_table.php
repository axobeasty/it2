<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_schedule_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('employees')->cascadeOnDelete();
            $table->date('week_start_date');
            $table->unsignedTinyInteger('weekday');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('subject_title', 255);
            $table->string('room', 64)->nullable();
            $table->string('building', 32);
            $table->timestamps();

            $table->index(['group_id', 'week_start_date', 'weekday']);
        });

        $now = now();
        $assign = [
            ['Студент', 'schedule_my'],
            ['Администратор', 'schedule_constructor'],
            ['Заместитель директора', 'schedule_constructor'],
            ['Директор', 'schedule_constructor'],
            ['Преподаватель', 'schedule_constructor'],
        ];

        foreach ($assign as [$roleName, $pageKey]) {
            $roleId = DB::table('roles')->where('name', $roleName)->value('id');
            if ($roleId) {
                DB::table('role_page_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'page_key' => $pageKey],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('group_schedule_entries');
        DB::table('role_page_permissions')->whereIn('page_key', ['schedule_my', 'schedule_constructor'])->delete();
    }
};
