<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared('
            CREATE TRIGGER after_grades_insert
            AFTER INSERT ON grades
            FOR EACH ROW
            BEGIN
                UPDATE student_profiles
                SET final_average = (
                    SELECT AVG(grade)
                    FROM grades
                    WHERE student_profile_id = NEW.student_profile_id
                )
                WHERE id = NEW.student_profile_id;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER after_grades_update
            AFTER UPDATE ON grades
            FOR EACH ROW
            BEGIN
                UPDATE student_profiles
                SET final_average = (
                    SELECT AVG(grade)
                    FROM grades
                    WHERE student_profile_id = NEW.student_profile_id
                )
                WHERE id = NEW.student_profile_id;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER after_grades_delete
            AFTER DELETE ON grades
            FOR EACH ROW
            BEGIN
                UPDATE student_profiles
                SET final_average = (
                    SELECT AVG(grade)
                    FROM grades
                    WHERE student_profile_id = OLD.student_profile_id
                )
                WHERE id = OLD.student_profile_id;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS after_grades_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS after_grades_update');
        DB::unprepared('DROP TRIGGER IF EXISTS after_grades_delete');
    }
};
