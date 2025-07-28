<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GradeLevel;
use App\Models\SchoolYear;
use App\Models\Classes;
use App\Models\Section;
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

        try {
            $gradeLevel->delete();

            return redirect()->route('admin.grade-levels.index')
                ->with('success', 'Grade Level deleted successfully.');
        } catch (\Throwable $th) {
            return redirect()->route('admin.grade-levels.index')
                ->with('error', 'Grade Level with active sections cannot be deleted.');
        }
    }
}
