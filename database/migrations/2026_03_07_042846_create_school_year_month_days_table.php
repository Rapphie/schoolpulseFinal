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
        if (! Schema::hasTable('school_year_month_days')) {
            Schema::create('school_year_month_days', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_year_id')->constrained()->onDelete('cascade');
                $table->tinyInteger('month')->unsigned();
                $table->unsignedSmallInteger('school_days')->default(0);
                $table->timestamps();
                $table->unique(['school_year_id', 'month']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_year_month_days');
    }
};
