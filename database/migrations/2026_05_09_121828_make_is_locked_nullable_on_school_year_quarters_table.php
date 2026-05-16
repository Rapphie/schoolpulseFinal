<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_year_quarters', function (Blueprint $table) {
            $table->boolean('is_locked')->nullable()->default(null)->change();
        });

        DB::table('school_year_quarters')
            ->where('is_locked', 0)
            ->update(['is_locked' => null]);
    }

    public function down(): void
    {
        DB::table('school_year_quarters')
            ->whereNull('is_locked')
            ->update(['is_locked' => 0]);

        Schema::table('school_year_quarters', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false)->nullable(false)->change();
        });
    }
};
