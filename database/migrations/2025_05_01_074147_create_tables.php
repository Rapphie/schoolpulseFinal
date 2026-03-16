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

        Schema::create('school_years', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('profile_picture')->nullable();
            $table->unsignedBigInteger('role_id');
            $table->rememberToken();
            $table->timestamps();

            // Foreign Key(s)
            $table->foreign('role_id')->references('id')->on('roles');
        });

        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('phone')->nullable();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->string('qualification')->nullable();
            $table->enum('status', ['active', 'on-leave', 'inactive'])->nullable();
            $table->timestamps();

            // Foreign Key(s)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('grade_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Grade 1", "Grade 2", etc.
            $table->integer('level'); // Numeric value of grade level (1, 2, 3, etc.)
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('grade_level_id');
            $table->string('description')->nullable();
            $table->timestamps();

            // Foreign Key(s)
            $table->foreign('grade_level_id')->references('id')->on('grade_levels')->onDelete('cascade');
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grade_level_id');
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Foreign Key(s)
            $table->foreign('grade_level_id')->references('id')->on('grade_levels');
        });

        Schema::create('llc', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('section_id');
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('school_year_id');
            $table->integer('quarter');
            $table->integer('total_students');
            $table->integer('total_items');
            $table->timestamps();

            // Foreign Key(s)
            $table->foreign('subject_id')->references('id')->on('subjects');
            $table->foreign('section_id')->references('id')->on('sections');
            $table->foreign('school_year_id')->references('id')->on('school_years');
            $table->foreign('teacher_id')->references('id')->on('teachers');
        });

        Schema::create('llc_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('llc_id');
            $table->integer('item_number');
            $table->integer('students_wrong');
            $table->string('category_name');
            $table->integer('item_start');
            $table->integer('item_end');
            $table->timestamps();

            // Foreign Key(s)
            $table->foreign('llc_id')->references('id')->on('llc')->onDelete('cascade');
        });

        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('phone');
            $table->enum('relationship', ['parent', 'sibling', 'relative', 'guardian']);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Foreign Key(s)
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_id')->unique()->nullable();
            $table->string('lrn')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('birthdate')->nullable();
            $table->text('address')->nullable();
            $table->string('family_income')->nullable();
            $table->string('parent_education')->nullable();
            $table->unsignedBigInteger('guardian_id')->nullable();
            $table->date('enrollment_date')->default(now());
            $table->timestamps();

            // Foreign Key(s)
            $table->foreign('guardian_id')->references('id')->on('guardians')->onDelete('set null');
        });

        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('section_id');
            $table->unsignedBigInteger('school_year_id');
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->integer('capacity')->nullable();
            $table->timestamps();

            // Foreign Key(s)
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
            $table->foreign('school_year_id')->references('id')->on('school_years')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('set null');

            // A section should only be offered once per school year
            $table->unique(['section_id', 'school_year_id']);
        });

        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id');
            $table->json('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room')->nullable();
            $table->timestamps();

            // Foreign Key(s)
            $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
        });

        Schema::create('grades', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('school_year_id');
            $table->float('grade');
            $table->string('quarter');
            $table->timestamps();

            // Foreign Key(s)
            $table->foreign('student_id')->references('id')->on('students');
            $table->foreign('subject_id')->references('id')->on('subjects');
            $table->foreign('teacher_id')->references('id')->on('teachers');

            $table->primary(['student_id', 'subject_id', 'quarter'], 'grades_primary_key');
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id');
            $table->time('time_in')->nullable();
            $table->enum('status', ['present', 'absent', 'late', 'excused']);
            $table->date('date');
            $table->string('quarter');
            $table->unsignedBigInteger('school_year_id');
            $table->timestamps();

            // Foreign Key(s)
            $table->foreign('student_id')->references('id')->on('students');
            $table->foreign('subject_id')->references('id')->on('subjects');
            $table->foreign('teacher_id')->references('id')->on('teachers');
            $table->foreign('school_year_id')->references('id')->on('school_years');
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('school_year_id');
            $table->date('enrollment_date')->default(now());
            $table->enum('status', ['unenrolled', 'enrolled', 'graduated', 'transferred'])->default('unenrolled');
            $table->timestamps();

            // Foreign Key(s)
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            $table->foreign('school_year_id')->references('id')->on('school_years')->onDelete('cascade');

            $table->unique(['student_id', 'school_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tables in reverse order to avoid foreign key constraints
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('parent_student');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('llc_items');
        Schema::dropIfExists('llc');
        Schema::dropIfExists('students');
        Schema::dropIfExists('teachers');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('grade_levels');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
    }
};
