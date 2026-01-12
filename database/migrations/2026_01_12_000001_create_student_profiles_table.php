<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * student_profiles: One row per student per school year.
     * Captures the student's grade level and academic summary for that year.
     * Enrollment records can reference this profile for easier tracking.
     */
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('school_year_id');
            $table->unsignedBigInteger('grade_level_id');
            $table->decimal('final_average', 5, 2)->nullable(); // Computed end-of-year average
            $table->enum('status', ['active', 'promoted', 'retained', 'transferred', 'dropped', 'graduated'])->default('active');
            $table->text('remarks')->nullable();
            $table->timestamps();

            // Each student can only have one profile per school year
            $table->unique(['student_id', 'school_year_id'], 'student_profile_unique');

            // Foreign keys
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('school_year_id')->references('id')->on('school_years')->onDelete('cascade');
            $table->foreign('grade_level_id')->references('id')->on('grade_levels')->onDelete('cascade');
        });

        // Add optional reference from enrollments to student_profiles
        Schema::table('enrollments', function (Blueprint $table) {
            $table->unsignedBigInteger('student_profile_id')->nullable()->after('school_year_id');
            $table->foreign('student_profile_id')->references('id')->on('student_profiles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['student_profile_id']);
            $table->dropColumn('student_profile_id');
        });

        Schema::dropIfExists('student_profiles');
    }
};
