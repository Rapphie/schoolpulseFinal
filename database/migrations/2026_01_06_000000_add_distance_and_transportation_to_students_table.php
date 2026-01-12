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
        Schema::table('students', function (Blueprint $table) {
            // Adding columns needed for Table 1 Features (Demographics)
            $table->decimal('distance_km', 8, 2)->nullable()->comment('Distance from school in km');
            $table->string('transportation')->nullable()->comment('Mode of transportation (e.g., Motorcycle, Tricycle, Jeepney, Walk)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['distance_km', 'transportation']);
        });
    }
};
