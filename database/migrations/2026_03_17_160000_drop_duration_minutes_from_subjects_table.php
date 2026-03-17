<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('subjects', 'duration_minutes')) {
            Schema::table('subjects', function (Blueprint $table): void {
                $table->dropColumn('duration_minutes');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('subjects', 'duration_minutes')) {
            Schema::table('subjects', function (Blueprint $table): void {
                $table->unsignedSmallInteger('duration_minutes')
                    ->nullable()
                    ->after('description')
                    ->comment('Expected duration in minutes for this subject (deprecated manual scheduling).');
            });
        }
    }
};
