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
        if (! Schema::hasColumn('students', 'family_income')) {
            Schema::table('students', function (Blueprint $table) {
                // Adding family_income column for ML feature extraction (Socioeconomic_Status)
                $table->string('family_income')->nullable()->after('address')->comment('Socioeconomic status (Low, Medium, High)');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('students', 'family_income')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('family_income');
            });
        }
    }
};
