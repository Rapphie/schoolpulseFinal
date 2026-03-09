<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('grade_level_subjects')) {
            Schema::create('grade_level_subjects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('grade_level_id')->constrained()->onDelete('cascade');
                $table->foreignId('subject_id')->constrained()->onDelete('cascade');
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('written_works_weight')->default(40);
                $table->unsignedInteger('performance_tasks_weight')->default(40);
                $table->unsignedInteger('quarterly_assessments_weight')->default(20);
                $table->timestamps();
                $table->unique(['grade_level_id', 'subject_id']);
            });
        }

        $timestamp = now();
        $rows = DB::table('subjects')
            ->whereNotNull('grade_level_id')
            ->select(['grade_level_id', 'id as subject_id', 'is_active'])
            ->get()
            ->map(function (object $subject) use ($timestamp): array {
                return [
                    'grade_level_id' => $subject->grade_level_id,
                    'subject_id' => $subject->subject_id,
                    'is_active' => (bool) $subject->is_active,
                    'written_works_weight' => 40,
                    'performance_tasks_weight' => 40,
                    'quarterly_assessments_weight' => 20,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })
            ->all();

        if ($rows !== []) {
            DB::table('grade_level_subjects')->insertOrIgnore($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_level_subjects');
    }
};
