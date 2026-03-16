<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add surrogate id and replace composite primary key with a unique index.
        Schema::table('grades', function (Blueprint $table) {
            // Only proceed if id column not present
            if (! Schema::hasColumn('grades', 'id')) {
                // Drop existing primary key so we can add an auto-increment id
                DB::statement('ALTER TABLE grades DROP PRIMARY KEY');
                $table->id()->first();
                // Preserve uniqueness of the former composite key (+ teacher & school year for safety)
                $table->unique(['student_id', 'subject_id', 'quarter', 'teacher_id', 'school_year_id'], 'grades_unique_student_subject_quarter_teacher_year');
            }
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            if (Schema::hasColumn('grades', 'id')) {
                // Drop the unique index and surrogate id, then recreate original composite primary key
                $table->dropUnique('grades_unique_student_subject_quarter_teacher_year');
                $table->dropColumn('id');
            }
        });
        // Restore original composite primary key (student_id, subject_id, quarter)
        DB::statement('ALTER TABLE grades ADD PRIMARY KEY (student_id, subject_id, quarter)');
    }
};
