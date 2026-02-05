<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds the 'enrolled' value to the student_profiles.status ENUM.
     *
     * @return void
     */
    public function up()
    {
        // Ensure any unexpected/legacy values are normalized to a valid value
        DB::statement("UPDATE student_profiles SET status = 'pending' WHERE status NOT IN ('active', pending,'enrolled','promoted','retained','transferred','transferee','dropped','graduated')");

        // Add 'enrolled' to permitted values. Keep default as 'active'.
        DB::statement("ALTER TABLE student_profiles MODIFY COLUMN status ENUM('active', 'enrolled','promoted','retained','transferred','transferee','dropped','graduated') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     *
     * Removes the 'enrolled' value by reverting to prior enum set.
     * @return void
     */
    public function down()
    {
        // Convert any 'enrolled' values back to 'active' before removing the enum value
        DB::statement("UPDATE student_profiles SET status = 'active' WHERE status = 'enrolled'");
        DB::statement("ALTER TABLE student_profiles MODIFY COLUMN status ENUM('active','promoted','retained','transferred','dropped','graduated') DEFAULT 'active'");
    }
};
