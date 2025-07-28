<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\GradeLevel;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subjects = Subject::all();
        $gradeLevels = GradeLevel::all();
        return view('admin.subjects.index', compact('subjects', 'gradeLevels'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:subjects,code',
            'description' => 'nullable|string',
            'grade_level_id' => 'required|exists:grade_levels,id',
        ]);

        $validated['is_active'] = $request->has('is_active');

        Subject::create($validated);

        return redirect()->route('admin.subjects.index')
            ->with('success', 'Subject created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Subject $subject)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Subject $subject)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:subjects,code,' . $subject->id,
            'description' => 'nullable|string',
            'grade_level_id' => 'required|exists:grade_levels,id',
            
        ]);

        $validated['is_active'] = $request->has('is_active');

        $subject->update($validated);

        return redirect()->route('admin.subjects.index')
            ->with('success', 'Subject updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Subject $subject)
    {
        try {
            $subject->delete();

            return redirect()->route('admin.subjects.index')
                ->with('success', 'subject deleted successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == '23000') { // Integrity constraint violation
                return redirect()->route('admin.subjects.index')
                    ->with('error', 'Cannot delete subject because they are referenced in other records.');
            }
            return redirect()->route('admin.subjects.index')
                ->with('error', 'An error occurred while deleting the subject.');
        } catch (\Throwable $th) {
            return redirect()->route('admin.subjects.index')
                ->with('error', 'An unexpected error occurred.');
        }
    }

    public function getSubjectsByGradeLevel($gradeLevel)
    {
        $subjects = Subject::where('grade_level_id', $gradeLevel)->get();
        return response()->json($subjects);
    }
}
