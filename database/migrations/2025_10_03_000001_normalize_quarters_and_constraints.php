<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add numeric quarter columns if not present
        Schema::table('grades', function (Blueprint $table) {
            if (! Schema::hasColumn('grades', 'quarter_int')) {
                $table->tinyInteger('quarter_int')->nullable()->after('quarter');
            }
        });
        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'quarter_int')) {
                $table->tinyInteger('quarter_int')->nullable()->after('quarter');
            }
        });

        // Backfill quarter_int from existing quarter text/numeric values
        DB::statement("
            UPDATE grades
            SET quarter_int = NULLIF(CAST(REGEXP_REPLACE(quarter, '[^0-9]', '') AS UNSIGNED), 0)
            WHERE quarter_int IS NULL
        ");

        DB::statement("
            UPDATE attendances
            SET quarter_int = NULLIF(CAST(REGEXP_REPLACE(quarter, '[^0-9]', '') AS UNSIGNED), 0)
            WHERE quarter_int IS NULL
        ");

        // Optional: add check constraints (PostgreSQL syntax). Wrapped in try/catch-like logic via silent execution.
        try {
            DB::statement('ALTER TABLE grades ADD CONSTRAINT chk_grades_quarter_int CHECK (quarter_int BETWEEN 1 AND 4)');
        } catch (Throwable $e) {
        }
        try {
            DB::statement('ALTER TABLE attendances ADD CONSTRAINT chk_attendances_quarter_int CHECK (quarter_int BETWEEN 1 AND 4)');
        } catch (Throwable $e) {
        }
        try {
            DB::statement('ALTER TABLE grades ADD CONSTRAINT chk_grades_grade_range CHECK (grade >= 0 AND grade <= 100)');
        } catch (Throwable $e) {
        }
    }

    public function down(): void
    {
        // Remove constraints if they exist and drop quarter_int columns
        foreach (
            [
                'grades' => ['chk_grades_quarter_int', 'chk_grades_grade_range'],
                'attendances' => ['chk_attendances_quarter_int'],
            ] as $table => $constraints
        ) {
            foreach ($constraints as $c) {
                try {
                    DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$c}");
                } catch (Throwable $e) {
                }
            }
        }
        Schema::table('grades', function (Blueprint $table) {
            if (Schema::hasColumn('grades', 'quarter_int')) {
                $table->dropColumn('quarter_int');
            }
        });
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'quarter_int')) {
                $table->dropColumn('quarter_int');
            }
        });
    }
};
