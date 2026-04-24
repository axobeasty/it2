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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('course')->nullable()->after('group_id');
            $table->string('record_book_number')->nullable()->after('course');
            $table->string('faculty')->nullable()->after('record_book_number');
            $table->string('department_name')->nullable()->after('faculty');
            $table->date('birth_date')->nullable()->after('department_name');
            $table->string('citizenship')->nullable()->after('birth_date');
            $table->string('phone')->nullable()->after('citizenship');
            $table->string('enrollment_year')->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'course',
                'record_book_number',
                'faculty',
                'department_name',
                'birth_date',
                'citizenship',
                'phone',
                'enrollment_year',
            ]);
        });
    }
};
