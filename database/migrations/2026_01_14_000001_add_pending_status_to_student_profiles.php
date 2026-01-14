<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the enum to include 'pending' status
        DB::statement("ALTER TABLE student_profiles MODIFY COLUMN status ENUM('pending', 'active', 'promoted', 'retained', 'transferred', 'dropped', 'graduated') DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First update any 'pending' records to 'active' to avoid data loss
        DB::table('student_profiles')->where('status', 'pending')->update(['status' => 'active']);

        // Revert to original enum without 'pending'
        DB::statement("ALTER TABLE student_profiles MODIFY COLUMN status ENUM('active', 'promoted', 'retained', 'transferred', 'dropped', 'graduated') DEFAULT 'active'");
    }
};
