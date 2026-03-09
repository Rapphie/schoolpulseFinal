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
        if (! Schema::hasTable('subjects')) {
            return;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM subjects LIKE 'grade_level_id'");

        if (! $column || strtoupper((string) $column->Null) === 'YES') {
            return;
        }

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['grade_level_id']);
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->unsignedBigInteger('grade_level_id')->nullable()->change();
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->foreign('grade_level_id')->references('id')->on('grade_levels');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('subjects')) {
            return;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM subjects LIKE 'grade_level_id'");

        if (! $column || strtoupper((string) $column->Null) === 'NO') {
            return;
        }

        $fallbackGradeLevelId = DB::table('grade_levels')->orderBy('id')->value('id');

        if ($fallbackGradeLevelId === null) {
            return;
        }

        DB::table('subjects')
            ->whereNull('grade_level_id')
            ->update(['grade_level_id' => $fallbackGradeLevelId]);

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['grade_level_id']);
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->unsignedBigInteger('grade_level_id')->nullable(false)->change();
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->foreign('grade_level_id')->references('id')->on('grade_levels');
        });
    }
};
