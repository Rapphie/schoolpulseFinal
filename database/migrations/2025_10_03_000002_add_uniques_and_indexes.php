<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Attendance uniqueness (allow nullable subject or class variations — adjust if needed)
        Schema::table('attendances', function (Blueprint $table) {
            // Only add if not exists (some databases require manual check; here we attempt and ignore on failure)
            try {
                $table->unique(['student_id', 'subject_id', 'date', 'school_year_id'], 'attendance_unique');
            } catch (Throwable $e) {
            }
            try {
                $table->index(['student_id', 'school_year_id'], 'attendance_student_year_idx');
            } catch (Throwable $e) {
            }
        });

        Schema::table('grades', function (Blueprint $table) {
            try {
                $table->index(['student_id', 'school_year_id'], 'grades_student_year_idx');
            } catch (Throwable $e) {
            }
        });

        Schema::table('enrollments', function (Blueprint $table) {
            try {
                $table->unique(['student_id', 'class_id'], 'enrollment_student_class_unique');
            } catch (Throwable $e) {
            }
            try {
                $table->index(['class_id'], 'enrollments_class_idx');
            } catch (Throwable $e) {
            }
        });

        Schema::table('assessment_scores', function (Blueprint $table) {
            try {
                $table->index(['student_id'], 'assessment_scores_student_idx');
            } catch (Throwable $e) {
            }
        });

        Schema::table('classes', function (Blueprint $table) {
            try {
                $table->index(['section_id', 'school_year_id'], 'classes_section_year_idx');
            } catch (Throwable $e) {
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            try {
                $table->dropUnique('attendance_unique');
            } catch (Throwable $e) {
            }
            try {
                $table->dropIndex('attendance_student_year_idx');
            } catch (Throwable $e) {
            }
        });
        Schema::table('grades', function (Blueprint $table) {
            try {
                $table->dropIndex('grades_student_year_idx');
            } catch (Throwable $e) {
            }
        });
        Schema::table('enrollments', function (Blueprint $table) {
            try {
                $table->dropUnique('enrollment_student_class_unique');
            } catch (Throwable $e) {
            }
            try {
                $table->dropIndex('enrollments_class_idx');
            } catch (Throwable $e) {
            }
        });
        Schema::table('assessment_scores', function (Blueprint $table) {
            try {
                $table->dropIndex('assessment_scores_student_idx');
            } catch (Throwable $e) {
            }
        });
        Schema::table('classes', function (Blueprint $table) {
            try {
                $table->dropIndex('classes_section_year_idx');
            } catch (Throwable $e) {
            }
        });
    }
};
