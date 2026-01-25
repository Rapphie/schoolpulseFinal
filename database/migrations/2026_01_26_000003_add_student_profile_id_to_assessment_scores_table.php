<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_scores', function (Blueprint $table) {
            if (!Schema::hasColumn('assessment_scores', 'student_profile_id')) {
                $table->unsignedBigInteger('student_profile_id')->nullable()->after('student_id');
                $table->foreign('student_profile_id')->references('id')->on('student_profiles')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assessment_scores', function (Blueprint $table) {
            if (Schema::hasColumn('assessment_scores', 'student_profile_id')) {
                $table->dropForeign(['student_profile_id']);
                $table->dropColumn('student_profile_id');
            }
        });
    }
};
