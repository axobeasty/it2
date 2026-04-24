<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_attempts', function (Blueprint $table) {
            $table->index(['student_id', 'test_id', 'submitted_at'], 'test_attempts_student_test_submitted_idx');
            $table->index(['group_id', 'submitted_at'], 'test_attempts_group_submitted_idx');
        });

        Schema::table('inv_numbers', function (Blueprint $table) {
            $table->index(['employees_id', 'date_out', 'id'], 'inv_numbers_employee_active_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['employee_id', 'status', 'created_at'], 'orders_employee_status_created_idx');
            $table->index(['category_id', 'created_at'], 'orders_category_created_idx');
        });

        Schema::table('group_schedule_entries', function (Blueprint $table) {
            $table->index(['week_start_date', 'group_id', 'weekday', 'start_time'], 'group_schedule_week_group_time_idx');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->index(['role_id', 'fio'], 'employees_role_fio_idx');
            $table->index(['group_id'], 'employees_group_idx');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('employees_role_fio_idx');
            $table->dropIndex('employees_group_idx');
        });

        Schema::table('group_schedule_entries', function (Blueprint $table) {
            $table->dropIndex('group_schedule_week_group_time_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_employee_status_created_idx');
            $table->dropIndex('orders_category_created_idx');
        });

        Schema::table('inv_numbers', function (Blueprint $table) {
            $table->dropIndex('inv_numbers_employee_active_idx');
        });

        Schema::table('test_attempts', function (Blueprint $table) {
            $table->dropIndex('test_attempts_student_test_submitted_idx');
            $table->dropIndex('test_attempts_group_submitted_idx');
        });
    }
};
