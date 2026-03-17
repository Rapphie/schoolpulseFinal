<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $subjects = Subject::query()->with('gradeLevel')->orderBy('name')->get();
        $gradeLevels = GradeLevel::query()->orderBy('level')->get();
        $gradeLevelSubjects = GradeLevelSubject::query()
            ->with(['gradeLevel', 'subject'])
            ->get()
            ->sortBy([
                fn (GradeLevelSubject $gradeLevelSubject) => $gradeLevelSubject->gradeLevel?->level ?? PHP_INT_MAX,
                fn (GradeLevelSubject $gradeLevelSubject) => mb_strtolower(
                    (string) $gradeLevelSubject->subject?->name
                ),
            ])
            ->values();

        $selectedGradeLevel = $request->integer('grade_level') ?: null;

        return view('admin.subjects.index', compact('subjects', 'gradeLevels', 'gradeLevelSubjects', 'selectedGradeLevel'));
    }

    public function create()
    {
        return redirect()->route('admin.subjects.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:subjects,name',
            'code' => 'required|string|max:100|unique:subjects,code',
            'description' => 'nullable|string',
        ]);

        try {
            Subject::query()->create([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'description' => $validated['description'] ?? null,
                'grade_level_id' => null,
                'is_active' => true,
            ]);

            return redirect()->route('admin.subjects.index')
                ->with('success', 'Subject created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating subject: '.$e->getMessage());

            return back()
                ->withInput()
                ->with('error', 'An error occurred while creating the subject. Please try again.');
        }
    }

    public function show(Subject $subject)
    {
        //
    }

    public function edit(Subject $subject): View
    {
        return view('admin.subjects.edit', compact('subject'));
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:subjects,name,'.$subject->id,
            'code' => 'required|string|max:50|unique:subjects,code,'.$subject->id,
            'description' => 'nullable|string',
        ]);

        $subject->update($validated);

        return redirect()->route('admin.subjects.index')
            ->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        try {
            $subject->delete();

            return redirect()->route('admin.subjects.index')
                ->with('success', 'subject deleted successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == '23000') {
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
        $subjects = Subject::query()
            ->where('is_active', true)
            ->where(function ($query) use ($gradeLevel) {
                $query->whereHas('gradeLevelSubjects', function ($gradeLevelSubjectQuery) use ($gradeLevel) {
                    $gradeLevelSubjectQuery
                        ->where('grade_level_id', $gradeLevel)
                        ->where('is_active', true);
                })->orWhere('grade_level_id', $gradeLevel);
            })
            ->select('subjects.*')
            ->distinct()
            ->orderBy('name')
            ->get();

        return response()->json($subjects);
    }

    public function getCatalogSubjects()
    {
        $subjects = Subject::query()->orderBy('name')->get();

        return response()->json($subjects);
    }
}
