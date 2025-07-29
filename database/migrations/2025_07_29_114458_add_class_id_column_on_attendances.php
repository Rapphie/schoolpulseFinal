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
        Schema::table('attendances', function (Blueprint $table) {
            // Add the class_id column after student_id for better organization
            $table->unsignedBigInteger('class_id')->after('student_id');

            // Add the foreign key constraint to link it to the classes table
            $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Drop the foreign key first
            $table->dropForeign(['class_id']);
            // Then drop the column
            $table->dropColumn('class_id');
        });
    }
};
