<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Backfill any existing null values to safe defaults to avoid altering failures
        DB::table('schedules')->whereNull('start_time')->update(['start_time' => '07:00:00']);
        DB::table('schedules')->whereNull('end_time')->update(['end_time' => '08:00:00']);

        Schema::table('schedules', function (Blueprint $table) {
            // Ensure you have doctrine/dbal installed to run the change() method
            $table->time('start_time')->nullable(false)->change();
            $table->time('end_time')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->time('start_time')->nullable()->change();
            $table->time('end_time')->nullable()->change();
        });
    }
};
