<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_feature_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('school_year_id');
            $table->json('features'); // Ordered feature list or key-value map
            $table->string('model_version');
            $table->timestamp('computed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['student_id', 'school_year_id', 'model_version'], 'snapshot_student_year_model_unique');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('school_year_id')->references('id')->on('school_years')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_feature_snapshots');
    }
};
