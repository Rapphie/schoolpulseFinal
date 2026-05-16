<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            DELETE q1 FROM school_year_quarters q1
            INNER JOIN school_year_quarters q2
            WHERE q1.id > q2.id
              AND q1.school_year_id = q2.school_year_id
              AND q1.quarter = q2.quarter
        ');

        Schema::table('school_year_quarters', function (Blueprint $table) {
            $table->unique(['school_year_id', 'quarter'], 'uq_school_year_quarters_year_quarter');
        });
    }

    public function down(): void
    {
        Schema::table('school_year_quarters', function (Blueprint $table) {
            $table->dropUnique('uq_school_year_quarters_year_quarter');
        });
    }
};
