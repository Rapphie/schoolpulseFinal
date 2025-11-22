<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('llc', function (Blueprint $table) {
            $table->string('exam_title')->nullable()->after('quarter');
        });
    }

    public function down(): void
    {
        Schema::table('llc', function (Blueprint $table) {
            $table->dropColumn('exam_title');
        });
    }
};
