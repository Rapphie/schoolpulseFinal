<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add new varchar column if not exists
        if (! Schema::hasColumn('guardians', 'phone_varchar')) {
            Schema::table('guardians', function (Blueprint $table) {
                $table->string('phone_varchar', 25)->nullable()->after('user_id');
            });
        }

        // Copy numeric phone to string preserving value (MySQL syntax)
        DB::statement('UPDATE guardians SET phone_varchar = CAST(phone AS CHAR) WHERE phone_varchar IS NULL');

        // Drop old column and rename
        Schema::table('guardians', function (Blueprint $table) {
            if (Schema::hasColumn('guardians', 'phone')) {
                $table->dropColumn('phone');
            }
        });

        Schema::table('guardians', function (Blueprint $table) {
            $table->renameColumn('phone_varchar', 'phone');
        });
    }

    public function down(): void
    {
        // Recreate integer column
        Schema::table('guardians', function (Blueprint $table) {
            $table->unsignedBigInteger('phone_int')->nullable()->after('user_id');
        });

        // Copy back numeric-only values safely
        try {
            DB::statement("
                UPDATE guardians
                SET phone_int = NULLIF(CAST(REGEXP_REPLACE(phone, '[^0-9]', '') AS UNSIGNED), 0)
                WHERE phone_int IS NULL
            ");
        } catch (Throwable $e) {
            // Silent catch if REGEXP_REPLACE unsupported (older MySQL)
        }

        Schema::table('guardians', function (Blueprint $table) {
            $table->dropColumn('phone');
            $table->renameColumn('phone_int', 'phone');
        });
    }
};
