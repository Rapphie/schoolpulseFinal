<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GradeLevel;
use App\Models\SchoolYear;
use App\Models\Classes;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GradeLevelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $gradeLevels = GradeLevel::orderBy('level')->get();
        return view('admin.grade-levels.index', compact('gradeLevels'));
    }

    /**
     * Show the form for creating a new resource.
     */


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $activeSchoolYear = SchoolYear::active()->first();
        if (!$activeSchoolYear) {
            return back()->with('error', 'Cannot create grade level because no school year is active.');
        }

        $validatedGrade = $request->validate([
            'name' => 'required|string|max:255|unique:grade_levels,name',
            'level' => 'required|integer|min:1|unique:grade_levels,level',
        ]);

        $validatedSections = $request->validate([
            'sections'   => 'required|array|min:1',
            'sections.*' => 'required|string|max:255'
        ]);

        try {
            DB::transaction(function () use ($validatedGrade, $validatedSections, $activeSchoolYear) {

                $gradeLevel = GradeLevel::create($validatedGrade);

                // Define the subjects array
                $subjects = [
                    ['level' => '1', 'name' => 'Mother Tongue 1', 'code' => 'MT1', 'is_active' => true],
                    ['level' => '2', 'name' => 'Mother Tongue 2', 'code' => 'MT2', 'is_active' => true],
                    ['level' => '3', 'name' => 'Mother Tongue 3', 'code' => 'MT3', 'is_active' => true],
                    ['level' => '1', 'name' => 'Filipino 1', 'code' => 'F1', 'is_active' => true],
                    ['level' => '2', 'name' => 'Filipino 2', 'code' => 'F2', 'is_active' => true],
                    ['level' => '3', 'name' => 'Filipino 3', 'code' => 'F3', 'is_active' => true],
                    ['level' => '4', 'name' => 'Filipino 4', 'code' => 'F4', 'is_active' => true],
                    ['level' => '5', 'name' => 'Filipino 5', 'code' => 'F5', 'is_active' => true],
                    ['level' => '6', 'name' => 'Filipino 6', 'code' => 'F6', 'is_active' => true],
                    ['level' => '1', 'name' => 'Mathematics 1', 'code' => 'M1', 'is_active' => true],
                    ['level' => '2', 'name' => 'Mathematics 2', 'code' => 'M2', 'is_active' => true],
                    ['level' => '3', 'name' => 'Mathematics 3', 'code' => 'M3', 'is_active' => true],
                    ['level' => '4', 'name' => 'Mathematics 4', 'code' => 'M4', 'is_active' => true],
                    ['level' => '5', 'name' => 'Mathematics 5', 'code' => 'M5', 'is_active' => true],
                    ['level' => '6', 'name' => 'Mathematics 6', 'code' => 'M6', 'is_active' => true],
                    ['level' => '3', 'name' => 'Science 3', 'code' => 'S3', 'is_active' => true],
                    ['level' => '4', 'name' => 'Science 4', 'code' => 'S4', 'is_active' => true],
                    ['level' => '5', 'name' => 'Science 5', 'code' => 'S5', 'is_active' => true],
                    ['level' => '6', 'name' => 'Science 6', 'code' => 'S6', 'is_active' => true],
                    ['level' => '1', 'name' => 'Araling Panlipunan 1', 'code' => 'AP1', 'is_active' => true],
                    ['level' => '2', 'name' => 'Araling Panlipunan 2', 'code' => 'AP2', 'is_active' => true],
                    ['level' => '3', 'name' => 'Araling Panlipunan 3', 'code' => 'AP3', 'is_active' => true],
                    ['level' => '4', 'name' => 'Araling Panlipunan 4', 'code' => 'AP4', 'is_active' => true],
                    ['level' => '5', 'name' => 'Araling Panlipunan 5', 'code' => 'AP5', 'is_active' => true],
                    ['level' => '6', 'name' => 'Araling Panlipunan 6', 'code' => 'AP6', 'is_active' => true],
                    ['level' => '1', 'name' => 'Music 1', 'code' => 'MU1', 'is_active' => true],
                    ['level' => '2', 'name' => 'Music 2', 'code' => 'MU2', 'is_active' => true],
                    ['level' => '3', 'name' => 'Music 3', 'code' => 'MU3', 'is_active' => true],
                    ['level' => '4', 'name' => 'Music 4', 'code' => 'MU4', 'is_active' => true],
                    ['level' => '5', 'name' => 'Music 5', 'code' => 'MU5', 'is_active' => true],
                    ['level' => '6', 'name' => 'Music 6', 'code' => 'MU6', 'is_active' => true],
                    ['level' => '1', 'name' => 'Arts 1', 'code' => 'A1', 'is_active' => true],
                    ['level' => '2', 'name' => 'Arts 2', 'code' => 'A2', 'is_active' => true],
                    ['level' => '3', 'name' => 'Arts 3', 'code' => 'A3', 'is_active' => true],
                    ['level' => '4', 'name' => 'Arts 4', 'code' => 'A4', 'is_active' => true],
                    ['level' => '5', 'name' => 'Arts 5', 'code' => 'A5', 'is_active' => true],
                    ['level' => '6', 'name' => 'Arts 6', 'code' => 'A6', 'is_active' => true],
                    ['level' => '1', 'name' => 'Physical Education 1', 'code' => 'PE1', 'is_active' => true],
                    ['level' => '2', 'name' => 'Physical Education 2', 'code' => 'PE2', 'is_active' => true],
                    ['level' => '3', 'name' => 'Physical Education 3', 'code' => 'PE3', 'is_active' => true],
                    ['level' => '4', 'name' => 'Physical Education 4', 'code' => 'PE4', 'is_active' => true],
                    ['level' => '5', 'name' => 'Physical Education 5', 'code' => 'PE5', 'is_active' => true],
                    ['level' => '6', 'name' => 'Physical Education 6', 'code' => 'PE6', 'is_active' => true],
                    ['level' => '1', 'name' => 'Health 1', 'code' => 'H1', 'is_active' => true],
                    ['level' => '2', 'name' => 'Health 2', 'code' => 'H2', 'is_active' => true],
                    ['level' => '3', 'name' => 'Health 3', 'code' => 'H3', 'is_active' => true],
                    ['level' => '4', 'name' => 'Health 4', 'code' => 'H4', 'is_active' => true],
                    ['level' => '5', 'name' => 'Health 5', 'code' => 'H5', 'is_active' => true],
                    ['level' => '6', 'name' => 'Health 6', 'code' => 'H6', 'is_active' => true],
                ];

                // Filter subjects based on the grade level created
                $gradeLevelSubjects = array_filter($subjects, function ($subject) use ($gradeLevel) {
                    return $subject['level'] == $gradeLevel->level;
                });

                // Create the subjects
                foreach ($gradeLevelSubjects as $subjectData) {
                    Subject::create([
                        'grade_level_id' => $gradeLevel->id,
                        'name' => $subjectData['name'],
                        'code' => $subjectData['code'],
                        'is_active' => $subjectData['is_active'],
                    ]);
                }


                foreach ($validatedSections['sections'] as $sectionName) {
                    // Create the timeless Section record
                    $section = Section::firstOrCreate(
                        [
                            'name' => $sectionName,
                            'grade_level_id' => $gradeLevel->id
                        ]
                    );

                    // Create the Class record for the active school year
                    Classes::create([
                        'section_id' => $section->id,
                        'school_year_id' => $activeSchoolYear->id,
                        'capacity' => 40,
                    ]);
                }
            });
        } catch (\Throwable $th) {
            return redirect()->route('admin.grade-levels.index')->with('error', 'Incorrect input or duplicate grade level');
        }


        return redirect()->route('admin.grade-levels.index')->with('success', 'Grade Level and its sections have been created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, GradeLevel $gradeLevel)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:grade_levels,name,' . $gradeLevel->id,
            'level' => 'required|integer|min:1|max:12|unique:grade_levels,level,' . $gradeLevel->id,
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $gradeLevel->update($validated);

        return redirect()->route('admin.grade-levels.index')
            ->with('success', 'Grade Level updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GradeLevel $gradeLevel)
    {
        $activeSchoolYear = SchoolYear::where('is_active', true)->first();

        if ($activeSchoolYear) {
            $hasActiveSections = $gradeLevel->sections()
                ->whereHas('classes', function ($query) use ($activeSchoolYear) {
                    $query->where('school_year_id', $activeSchoolYear->id);
                })->exists();

            if ($hasActiveSections) {
                return redirect()->route('admin.grade-levels.index')
                    ->with('error', 'Grade Level with active sections cannot be deleted.');
            }
        }

        try {
            $gradeLevel->delete();

            return redirect()->route('admin.grade-levels.index')
                ->with('success', 'Grade Level deleted successfully.');
        } catch (\Throwable $th) {
            return redirect()->route('admin.grade-levels.index')
                ->with('error', 'Grade Level cannot be deleted. It may be linked to other active records.');
        }
    }
}
