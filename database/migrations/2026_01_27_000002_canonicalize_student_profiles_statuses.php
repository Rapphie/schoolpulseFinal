<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Map legacy values to canonical statuses and update the ENUM to a
     * minimal, school-focused set: pending, enrolled, promoted, retained,
     * transferred, dropped, graduated.
     *
     * @return void
     */
    public function up()
    {
        // Normalize known legacy values
        DB::statement("UPDATE student_profiles SET status = 'enrolled' WHERE status = 'active'");
        DB::statement("UPDATE student_profiles SET status = 'transferred' WHERE status = 'transferee'");

        // Any remaining unknown values -> 'pending' (safe default)
        DB::statement("UPDATE student_profiles SET status = 'pending' WHERE status NOT IN ('pending','enrolled','promoted','retained','transferred','dropped','graduated')");

        // Alter enum to canonical set, default to 'pending'
        DB::statement("ALTER TABLE student_profiles MODIFY COLUMN status ENUM('pending','enrolled','promoted','retained','transferred','dropped','graduated') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     *
     * Revert to previous enum (including 'active' and 'transferee').
     * @return void
     */
    public function down()
    {
        // Map back canonical values to previous equivalents where sensible
        DB::statement("UPDATE student_profiles SET status = 'active' WHERE status = 'enrolled'");
        DB::statement("UPDATE student_profiles SET status = 'transferee' WHERE status = 'transferred'");

        DB::statement("ALTER TABLE student_profiles MODIFY COLUMN status ENUM('active','enrolled','promoted','retained','transferred','transferee','dropped','graduated') DEFAULT 'active'");
    }
};
