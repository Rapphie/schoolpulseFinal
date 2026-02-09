<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeEmail;
use App\Models\Classes;
use App\Models\GradeLevel;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        // Fetch all teachers and eagerly load the relationships needed by the view.
        // This is the most efficient way to get the data.
        $teachers = Teacher::with([
            'user',
            'subjects',
            'classes.section.gradeLevel',
        ])->get();
        // dd($teachers);

        $sections = Section::all();
        $subjects = Subject::all();
        $gradeLevels = GradeLevel::orderBy('level')->get();

        return view('admin.teachers.index', compact('teachers', 'subjects', 'sections', 'gradeLevels'));
    }

    /**
     * Display the specified teacher (read-only profile view).
     */
    public function show(User $teacher)
    {
        $activeSchoolYear = SchoolYear::where('is_active', true)->first();

        $advisoryClasses = collect();
        $scheduledSubjects = collect();

        $teacherModel = $teacher->teacher;
        if ($teacherModel && $activeSchoolYear) {
            $advisoryClasses = Classes::with(['section.gradeLevel'])
                ->where('teacher_id', $teacherModel->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->get();

            $scheduledSubjects = Schedule::where('teacher_id', $teacherModel->id)
                ->with('class.section', 'subject')
                ->whereHas('class', function ($query) use ($activeSchoolYear) {
                    $query->where('school_year_id', $activeSchoolYear->id);
                })
                ->get();
        }

        return view('admin.teachers.show', compact('teacher', 'advisoryClasses', 'scheduledSubjects'));
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
        $activeSchoolYear = SchoolYear::where('is_active', true)->first();

        if (! $activeSchoolYear) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Please set an active school year before assigning advisory classes.');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
            'qualification' => 'nullable|string',
            'status' => 'nullable|in:active,on-leave,inactive',
            'section_ids' => 'nullable|array',
            'section_ids.*' => 'nullable|integer|exists:sections,id',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $sectionIds = collect($validated['section_ids'] ?? [])
            ->filter()
            ->unique()
            ->values();

        unset($validated['section_ids']);

        DB::beginTransaction();
        try {
            $password = strtolower($validated['first_name']).strtolower(substr($validated['last_name'], 0, 1)).date('Y');

            // Create user first
            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make($password),
                'profile_picture' => $request->hasFile('profile_picture') ? $request->file('profile_picture')->store('teachers/profile-pictures', 'public') : null,
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

            $conflicts = collect();

            if ($sectionIds->isNotEmpty()) {
                foreach ($sectionIds as $sectionId) {
                    $section = Section::with('gradeLevel')->find($sectionId);
                    if (! $section) {
                        continue;
                    }

                    $class = Classes::firstOrCreate([
                        'section_id' => $section->id,
                        'school_year_id' => $activeSchoolYear->id,
                    ]);

                    $gradeLevel = $section->gradeLevel;
                    $gradeValue = optional($gradeLevel)->level;
                    $isRestrictedGrade = ! is_null($gradeValue) && ! in_array($gradeValue, [4, 5, 6]);

                    if ($isRestrictedGrade && $class->teacher_id && $class->teacher_id !== $teacher->id) {
                        $existingTeacher = Teacher::with('user')->find($class->teacher_id);
                        $existingUser = optional($existingTeacher)->user;
                        $existingTeacherName = $existingUser
                            ? trim(($existingUser->first_name ?? '').' '.($existingUser->last_name ?? ''))
                            : 'another teacher';
                        $existingTeacherName = $existingTeacherName !== '' ? $existingTeacherName : 'another teacher';

                        $gradeLabel = optional($gradeLevel)->name ?? 'Grade '.$gradeValue;
                        $conflicts->push("{$gradeLabel} - {$section->name} is already handled by {$existingTeacherName}.");

                        continue;
                    }

                    if ($isRestrictedGrade) {
                        $existingAdvisory = Classes::where('teacher_id', $teacher->id)
                            ->where('school_year_id', $activeSchoolYear->id)
                            ->whereHas('section', function ($query) use ($gradeValue) {
                                $query->whereHas('gradeLevel', function ($q) use ($gradeValue) {
                                    $q->where('level', $gradeValue);
                                });
                            })
                            ->where('id', '!=', $class->id)
                            ->first();

                        if ($existingAdvisory) {
                            $gradeLabel = optional($gradeLevel)->name ?? 'Grade '.$gradeValue;
                            $conflicts->push("{$gradeLabel} - {$section->name}: Teacher is already assigned to ".optional($existingAdvisory->section)->name.'.');

                            continue;
                        }
                    }

                    $class->update(['teacher_id' => $teacher->id]);
                }
            }

            if ($conflicts->isNotEmpty()) {
                DB::rollBack();

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Advisory assignment failed: '.$conflicts->implode(' '));
            }

            Mail::to($user->email)->queue(new WelcomeEmail($user, $password));
            DB::commit();

            return redirect()->route('admin.teachers.index')
                ->with('success', 'Teacher created successfully.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'An error has occurred. Failed to save Teacher: '.$th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified teacher.
     */
    public function edit(User $teacher)
    {
        $activeSchoolYear = SchoolYear::where('is_active', true)->first();

        $advisoryClasses = collect();
        $scheduledSubjects = collect();
        $teacherId = optional($teacher->teacher)->id;

        if ($activeSchoolYear && $teacherId) {
            $advisoryClasses = Classes::where('teacher_id', $teacherId)
                ->where('school_year_id', $activeSchoolYear->id)
                ->with('section.gradeLevel')
                ->get();

            // Get the schedule entries for this teacher for the active year
            $scheduledSubjects = Schedule::where('teacher_id', $teacherId)
                ->with('class.section', 'subject')
                ->whereHas('class', function ($query) use ($activeSchoolYear) {
                    $query->where('school_year_id', $activeSchoolYear->id);
                })
                ->get();
        }

        return view('admin.teachers.edit', compact('teacher', 'advisoryClasses', 'scheduledSubjects'));
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
                Rule::unique('users')->ignore($teacher->id),
            ],
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
            'qualification' => 'nullable|string',
            'status' => 'nullable|in:active,on-leave,inactive',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        DB::beginTransaction();
        try {
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

            // Separate user and teacher fields
            $userFields = ['first_name', 'last_name', 'email', 'password', 'profile_picture', 'is_active'];
            $teacherFields = ['phone', 'gender', 'date_of_birth', 'address', 'qualification', 'status'];

            $userData = array_intersect_key($validated, array_flip($userFields));
            $teacherData = array_intersect_key($validated, array_flip($teacherFields));

            // Update the user record
            $teacher->update($userData);

            // Update the teacher record if it exists
            if ($teacher->teacher) {
                $teacher->teacher->update($teacherData);
            }

            DB::commit();

            return redirect()->route('admin.teachers.index')
                ->with('success', 'Teacher updated successfully.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update teacher: '.$th->getMessage());
        }
    }

    /**
     * Remove the specified teacher from storage.
     */
    public function destroy(Teacher $teacher)
    {
        // dd($teacher);
        DB::transaction(function () use ($teacher) {
            // Get the associated user before deleting the teacher
            $user = $teacher->user;

            // First, delete the teacher record.
            // The database schema should handle setting teacher_id to null in related tables.
            $teacher->delete();

            // Then, delete the user record.
            if ($user) {
                if ($user->profile_picture) {
                    Storage::disk('public')->delete($user->profile_picture);
                }
                $user->delete();
            }
        });

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Teacher deleted successfully.');
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
        if (! $section) {
            return response()->json(['error' => 'Section not found'], 404);
        }

        // Get subjects associated with the section
        $subjects = $section->subjects;

        return response()->json($subjects);
    }
}
