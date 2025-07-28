<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'school_year_id' => 'required|exists:school_years,id',
            'teacher_id' => 'nullable|exists:teachers,id',
        ]);

        // Check if a class with the same section and school year already exists
        $existingClass = SchoolClass::where('section_id', $validated['section_id'])
            ->where('school_year_id', $validated['school_year_id'])
            ->exists();

        if ($existingClass) {
            return redirect()->route('admin.sections.index')->with('error', 'A class for this section and school year already exists.');
        }

        SchoolClass::create($validated);

        return redirect()->route('admin.sections.index')->with('success', 'Class created successfully.');
    }

    public function manage(SchoolClass $class)
    {
        $class->load(['section.gradeLevel', 'schoolYear', 'teacher.user', 'students.user', 'subjects.teacher.user']);
        return view('admin.classes.manage', compact('class'));
    }
}
