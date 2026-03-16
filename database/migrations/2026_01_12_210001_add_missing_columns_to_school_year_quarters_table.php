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
        if (! Schema::hasTable('school_year_quarters')) {
            // If the table doesn't exist, create it with the expected schema.
            Schema::create('school_year_quarters', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_year_id')->nullable();
                $table->tinyInteger('quarter')->nullable()->comment('1-4 for Q1-Q4');
                $table->string('name')->nullable()->comment('e.g., First Quarter, Second Quarter');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->date('grade_submission_deadline')->nullable()->comment('Deadline for teachers to submit grades');
                $table->boolean('is_locked')->default(false)->comment('When true, prevents grade/attendance modifications');
                $table->timestamps();
            });
        } else {
            // Table exists — add any missing columns.
            Schema::table('school_year_quarters', function (Blueprint $table) {
                if (! Schema::hasColumn('school_year_quarters', 'school_year_id')) {
                    $table->unsignedBigInteger('school_year_id')->nullable()->after('id');
                }
                if (! Schema::hasColumn('school_year_quarters', 'quarter')) {
                    $table->tinyInteger('quarter')->nullable()->after('school_year_id')->comment('1-4 for Q1-Q4');
                }
                if (! Schema::hasColumn('school_year_quarters', 'name')) {
                    $table->string('name')->nullable()->after('quarter')->comment('e.g., First Quarter, Second Quarter');
                }
                if (! Schema::hasColumn('school_year_quarters', 'start_date')) {
                    $table->date('start_date')->nullable()->after('name');
                }
                if (! Schema::hasColumn('school_year_quarters', 'end_date')) {
                    $table->date('end_date')->nullable()->after('start_date');
                }
                if (! Schema::hasColumn('school_year_quarters', 'grade_submission_deadline')) {
                    $table->date('grade_submission_deadline')->nullable()->after('end_date')->comment('Deadline for teachers to submit grades');
                }
                if (! Schema::hasColumn('school_year_quarters', 'is_locked')) {
                    $table->boolean('is_locked')->default(false)->after('grade_submission_deadline')->comment('When true, prevents grade/attendance modifications');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('school_year_quarters')) {
            Schema::table('school_year_quarters', function (Blueprint $table) {
                if (Schema::hasColumn('school_year_quarters', 'is_locked')) {
                    $table->dropColumn('is_locked');
                }
                if (Schema::hasColumn('school_year_quarters', 'grade_submission_deadline')) {
                    $table->dropColumn('grade_submission_deadline');
                }
                if (Schema::hasColumn('school_year_quarters', 'end_date')) {
                    $table->dropColumn('end_date');
                }
                if (Schema::hasColumn('school_year_quarters', 'start_date')) {
                    $table->dropColumn('start_date');
                }
                if (Schema::hasColumn('school_year_quarters', 'name')) {
                    $table->dropColumn('name');
                }
                if (Schema::hasColumn('school_year_quarters', 'quarter')) {
                    $table->dropColumn('quarter');
                }
                if (Schema::hasColumn('school_year_quarters', 'school_year_id')) {
                    $table->dropColumn('school_year_id');
                }
            });
        }
    }
};
