<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Models\Subject;
use App\Models\Section;
use App\Mail\WelcomeEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    /**
     * Display a listing of teachers.
     */
    public function classes()
    {
        $classes = Section::all();
        $students = Student::all();

        return view('teacher.classes.view', compact('classes', 'students'));
    }

    public function index()
    {
        $teachers = User::whereHas('role', function ($query) {
            $query->where('name', 'teacher');
        })
            ->withCount('subjects')
            ->latest()
            ->get();
        $sections = Section::all();
        $subjects = Subject::all();

        return view('admin.teachers.index', compact('teachers', 'subjects', 'sections'));
    }

    /**
     * Show the form for creating a new teacher.
     */
    public function create()
    {
        $subjects = Subject::all();
        $sections = Section::all();
        return view('admin.teachers.create', compact('subjects', 'sections'));
    }

    /**
     * Store a newly created teacher in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'required|date',
            'address' => 'required|string',
            'qualification' => 'required|string',
            'status' => 'required|string',
            'section_id' => 'nullable|exists:sections,id',
        ]);

        $password = strtolower($validated['first_name']) . strtolower(substr($validated['last_name'], 0, 1)) . date('Y');

        // Create user first
        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($password),
            'profile_picture' => $request->file('profile_picture') ? $request->file('profile_picture')->store('teachers/profile-pictures', 'public') : null,
            'role_id' => 2,
        ]);

        // Create teacher record associated with the user
        $teacher = $user->teacher()->create([
            'phone' => $validated['phone'],
            'gender' => $validated['gender'],
            'date_of_birth' => $validated['date_of_birth'],
            'address' => $validated['address'],
            'qualification' => $validated['qualification'],
            'status' => $validated['status'],
        ]);

        // Handle advisory class assignment
        if ($request->filled('section_id')) {
            $section = Section::find($validated['section_id']);
            if ($section) {
                $section->teacher_id = $teacher->id;
                $section->save();
            }
        }

        // Send welcome email
        Mail::to($user->email)->send(new WelcomeEmail($user, $password));

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Teacher created successfully.');
    }

    /**
     * Display the specified teacher.
     */
    public function show(User $teacher)
    {
        // $this->authorize('view', $teacher);

        $teacher->load(['subjects', 'sections', 'classes']);
        return view('admin.teachers.show', compact('teacher'));
    }

    /**
     * Show the form for editing the specified teacher.
     */
    public function edit(User $teacher)
    {
        $subjects = Subject::all();
        $sections = Section::all();
        $teacher->load('subjects');

        return view('admin.teachers.edit', compact('teacher', 'subjects', 'sections'));
    }

    /**
     * Update the specified teacher in storage.
     */
    public function update(Request $request, User $teacher)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($teacher->id)
            ],
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
            'qualification' => 'nullable|string',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'subjects' => 'nullable|array',
            'subjects.*.id' => 'required|exists:subjects,id',
            'subjects.*.section_id' => 'required|exists:sections,id',
        ]);

        // Extract subjects from validated data to handle separately
        $subjects = [];
        if (isset($validated['subjects'])) {
            $subjects = collect($validated['subjects'])->mapWithKeys(function ($item) {
                return [$item['id'] => ['section_id' => $item['section_id']]];
            })->toArray();
            unset($validated['subjects']);
        }

        // Handle file upload
        if ($request->hasFile('profile_picture')) {
            // Delete old profile picture if exists
            if ($teacher->profile_picture) {
                Storage::disk('public')->delete($teacher->profile_picture);
            }
            $path = $request->file('profile_picture')->store('teachers/profile-pictures', 'public');
            $validated['profile_picture'] = $path;
        }

        // Update password if provided
        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $validated['is_active'] = $request->has('is_active');

        // Update the teacher record
        $teacher->update($validated);

        // Sync subjects
        if ($subjects !== null) {
            $teacher->subjects()->sync($subjects);
        } else {
            $teacher->subjects()->detach();
        }

        return redirect()->route('admin.teachers.show', $teacher)
            ->with('success', 'Teacher updated successfully.');
    }

    /**
     * Remove the specified teacher from storage.
     */
    public function destroy(User $teacher)
    {
        try {
            if ($teacher->profile_picture) {
                Storage::disk('public')->delete($teacher->profile_picture);
            }

            $teacher->delete();
            return back()
                ->with('success', 'Teacher deleted successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            return back()
                ->with('error', 'An unexpected error occurred.');
        } catch (\Throwable $th) {
            return back()
                ->with('error', $th);
        }
    }

    /**
     * Upload a document for the teacher.
     */
    public function uploadDocument(Request $request, User $teacher)
    {
        // $this->authorize('update', $teacher);

        // $validated = $request->validate([
        //     'document' => 'required|file|mimes:pdf,doc,docx|max:5120',
        //     'title' => 'required|string|max:255',
        //     'description' => 'nullable|string',
        // ]);

        // $path = $request->file('document')->store('teachers/documents', 'public');

        // $document = $teacher->documents()->create([
        //     'title' => $validated['title'],
        //     'description' => $validated['description'],
        //     'file_path' => $path,
        //     'file_type' => $request->file('document')->getClientOriginalExtension(),
        //     'file_size' => $request->file('document')->getSize(),
        // ]);

        // return response()->json([
        //     'success' => true,
        //     'document' => $document,
        //     'message' => 'Document uploaded successfully.'
        // ]);
    }

    /**
     * Delete a teacher's document.
     */
    public function deleteDocument($documentId)
    {
        // Implementation depends on your Document model and relationships
        // This is a placeholder implementation
        // $document = \App\Models\Document::findOrFail($documentId);
        // $this->authorize('delete', $document);

        // Storage::disk('public')->delete($document->file_path);
        // $document->delete();

        // return response()->json([
        //     'success' => true,
        //     'message' => 'Document deleted successfully.'
        // ]);
    }

    /**
     * Update teacher status (active/inactive).
     */
    public function updateStatus(Request $request, User $teacher)
    {
        // $this->authorize('update', $teacher);

        // $validated = $request->validate([
        //     'is_active' => 'required|boolean',
        // ]);

        // $teacher->update(['is_active' => $validated['is_active']]);

        // $status = $validated['is_active'] ? 'activated' : 'deactivated';

        // return response()->json([
        //     'success' => true,
        //     'message' => "Teacher account has been {$status} successfully."
        // ]);
    }

    public function getSubjectsBySection(Request $request, Section $section)
    {
        // Validate the section
        if (!$section) {
            return response()->json(['error' => 'Section not found'], 404);
        }

        // Get subjects associated with the section
        $subjects = $section->subjects;

        return response()->json($subjects);
    }
}
