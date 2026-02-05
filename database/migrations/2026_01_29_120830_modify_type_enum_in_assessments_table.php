<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE assessments MODIFY COLUMN type ENUM('written_works', 'performance_tasks', 'quarterly_assessments', 'oral_participation') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE assessments MODIFY COLUMN type ENUM('written_works', 'performance_tasks', 'quarterly_assessments') NOT NULL");
    }
};
