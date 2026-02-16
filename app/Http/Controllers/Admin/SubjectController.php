<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GradeLevel;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        return redirect()->route('admin.subjects.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'grade_level_id' => 'required|exists:grade_levels,id',
            'subjects' => 'required|array|min:1',
            'subjects.*.name' => 'required|string|max:255',
            'subjects.*.code' => 'required|string|max:100',
            'subjects.*.duration_minutes' => 'nullable|integer|min:15|max:480',
        ]);

        try {
            DB::beginTransaction();

            $gradeLevelId = $validated['grade_level_id'];

            foreach ($validated['subjects'] as $subjectData) {
                Subject::create([
                    'name' => $subjectData['name'],
                    'code' => $subjectData['code'],
                    'grade_level_id' => $gradeLevelId,
                    'duration_minutes' => $subjectData['duration_minutes'] ?? null,
                    'is_active' => true,
                ]);
            }

            DB::commit();

            return redirect()->route('admin.subjects.index')->with('success', 'Subjects added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding subjects: '.$e->getMessage());

            return back()->with('error', 'An error occurred while adding subjects. Please try again.');
        }
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
        return view('admin.subjects.edit', compact('subject'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:subjects,code,'.$subject->id,
            'description' => 'nullable|string',
            'grade_level_id' => 'required|exists:grade_levels,id',
            'duration_minutes' => 'nullable|integer|min:15|max:480',
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
