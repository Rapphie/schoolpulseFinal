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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id');
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->string('remarks');
            $table->tinyInteger('quarter');
            $table->string('school_year')->default(date('Y') . '-' . (date('Y') + 1));
            $table->timestamps();

            // Foreign Key(s)
            $table->foreign('student_id')->references('id')->on('students');
            $table->foreign('subject_id')->references('id')->on('subjects');
            $table->foreign('teacher_id')->references('id')->on('teachers');
        });


        Schema::create('grades', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id');
            $table->float('grade');
            $table->timestamps();

            // Foreign Key(s)
            $table->foreign('student_id')->references('id')->on('students');
            $table->foreign('subject_id')->references('id')->on('subjects');
            $table->foreign('teacher_id')->references('id')->on('teachers');
            $table->primary(['student_id', 'subject_id', 'teacher_id']);
        });

        Schema::create('grade_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Grade 1", "Grade 2", etc.
            $table->integer('level'); // Numeric value of grade level (1, 2, 3, etc.)
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('llc', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('section_id');
            $table->string('category_name');
            $table->string('description');
            $table->timestamps();

            // Foreign Key(s)
            $table->foreign('subject_id')->references('id')->on('subjects');
        });

        Schema::create('llc_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('llc_id');
            $table->timestamps();

            // Foreign Key(s)
            $table->foreign('llc_id')->references('id')->on('llc');
        });

        // Create parent_student pivot table
        Schema::create('parent_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('users');
            $table->foreignId('student_id')->constrained();
            $table->string('relationship');
            $table->timestamps();

            $table->unique(['parent_id', 'student_id']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('section_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id');
            $table->string('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room')->nullable();
            $table->timestamps();

            // Foreign Key(s)
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedTinyInteger('grade_level_id');
            $table->text('description');
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedInteger('capacity');
            $table->timestamps();

            // Foreign Key(s)
            $table->foreign('teacher_id')->references('id')->on('teachers');
        });

        Schema::create('section_subject', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('section_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id');
            $table->timestamps();

            // Foreign Key(s)
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->unsignedBigInteger('section_id');
            $table->string('qr_code');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            // Foreign Key(s)
            $table->foreign('section_id')->references('id')->on('sections');
            $table->foreign('user_id')->references('id')->on('users');
        });


        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('description');
            $table->integer('units')->default(1);
            $table->integer('hours_per_week')->default(1);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
        });

        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('phone')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->string('qualification')->nullable();
            $table->enum('status', ['active', 'on-leave', 'inactive'])->nullable();

            // Foreign Key(s)
            $table->foreign('user_id')->references('id')->on('users');
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_student');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('llc_items');
        Schema::dropIfExists('llc');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('section_subject');
        Schema::dropIfExists('students');
        Schema::dropIfExists('teachers');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('users');
        Schema::dropIfExists('grade_levels');
        Schema::dropIfExists('roles');
    }
};
