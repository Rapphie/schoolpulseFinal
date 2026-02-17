<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->index('school_year_id', 'grades_school_year_idx');
            $table->index('quarter', 'grades_quarter_idx');
            if (Schema::hasColumn('grades', 'student_profile_id')) {
                $table->index('student_profile_id', 'grades_student_profile_idx');
            }
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->index('date', 'attendances_date_idx');
            $table->index('status', 'attendances_status_idx');
            if (Schema::hasColumn('attendances', 'class_id')) {
                $table->index('class_id', 'attendances_class_idx');
            }
            if (Schema::hasColumn('attendances', 'student_profile_id')) {
                $table->index('student_profile_id', 'attendances_student_profile_idx');
            }
        });

        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'teacher_id')) {
                $table->index('teacher_id', 'enrollments_teacher_idx');
            }
            $table->index('status', 'enrollments_status_idx');
            if (Schema::hasColumn('enrollments', 'student_profile_id')) {
                $table->index('student_profile_id', 'enrollments_student_profile_idx');
            }
            if (Schema::hasColumn('enrollments', 'enrolled_by_user_id')) {
                $table->index('enrolled_by_user_id', 'enrollments_enrolled_by_user_idx');
            }
        });

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->index('status', 'student_profiles_status_idx');
            $table->index('created_by_teacher_id', 'student_profiles_created_by_teacher_idx');
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->index('quarter', 'assessments_quarter_idx');
            $table->index('type', 'assessments_type_idx');
        });

        if (Schema::hasColumn('assessment_scores', 'student_profile_id')) {
            Schema::table('assessment_scores', function (Blueprint $table) {
                $table->index('student_profile_id', 'assessment_scores_student_profile_idx');
            });
        }

        Schema::table('classes', function (Blueprint $table) {
            $table->index('teacher_id', 'classes_teacher_idx');
        });

        Schema::table('llc', function (Blueprint $table) {
            $table->index(['quarter', 'section_id', 'teacher_id'], 'llc_lookup_idx');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->index('guardian_id', 'students_guardian_idx');
        });

        // Add composite index for attendance summary queries
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'class_id')) {
                $table->index(['class_id', 'school_year_id', 'date'], 'attendances_summary_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropIndex('grades_school_year_idx');
            $table->dropIndex('grades_quarter_idx');
        });

        if (Schema::hasIndex('grades', 'grades_student_profile_idx')) {
            Schema::table('grades', function (Blueprint $table) {
                $table->dropIndex('grades_student_profile_idx');
            });
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_date_idx');
            $table->dropIndex('attendances_status_idx');
        });

        $attendanceIndexes = ['attendances_class_idx', 'attendances_student_profile_idx', 'attendances_summary_idx'];
        foreach ($attendanceIndexes as $index) {
            if (Schema::hasIndex('attendances', $index)) {
                Schema::table('attendances', function (Blueprint $table) use ($index) {
                    $table->dropIndex($index);
                });
            }
        }

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex('enrollments_status_idx');
        });

        $enrollmentIndexes = ['enrollments_teacher_idx', 'enrollments_student_profile_idx', 'enrollments_enrolled_by_user_idx'];
        foreach ($enrollmentIndexes as $index) {
            if (Schema::hasIndex('enrollments', $index)) {
                Schema::table('enrollments', function (Blueprint $table) use ($index) {
                    $table->dropIndex($index);
                });
            }
        }

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropIndex('student_profiles_status_idx');
            $table->dropIndex('student_profiles_created_by_teacher_idx');
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropIndex('assessments_quarter_idx');
            $table->dropIndex('assessments_type_idx');
        });

        if (Schema::hasIndex('assessment_scores', 'assessment_scores_student_profile_idx')) {
            Schema::table('assessment_scores', function (Blueprint $table) {
                $table->dropIndex('assessment_scores_student_profile_idx');
            });
        }

        Schema::table('classes', function (Blueprint $table) {
            $table->dropIndex('classes_teacher_idx');
        });

        Schema::table('llc', function (Blueprint $table) {
            $table->dropIndex('llc_lookup_idx');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('students_guardian_idx');
        });
    }
};
