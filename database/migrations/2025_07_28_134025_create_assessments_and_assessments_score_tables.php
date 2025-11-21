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

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('school_year_id');
            $table->string('name'); // Name for the assessment (e.g., "Quiz 1", "Midterm Exam")
            $table->enum('type', ['written_works', 'performance_tasks', 'quarterly_assessments']);
            $table->decimal('max_score', 8, 2);
            $table->integer('quarter');
            $table->date('assessment_date');
            $table->timestamps();

            // Foreign Key(s)
            $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
            $table->foreign('school_year_id')->references('id')->on('school_years')->onDelete('cascade');
        });

        Schema::create('assessment_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_id');
            $table->unsignedBigInteger('student_id');
            $table->decimal('score', 8, 2);
            $table->string('remarks')->nullable();
            $table->timestamps();

            // Foreign Key(s)
            $table->foreign('assessment_id')->references('id')->on('assessments')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');

            // A student should only have one score per assessment
            $table->unique(['assessment_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('assessment_scores');
    }
};
