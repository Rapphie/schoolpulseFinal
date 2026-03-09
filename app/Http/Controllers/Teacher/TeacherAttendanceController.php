<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Mail\AbsentAlertMail;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\SchoolYearQuarter;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\QuarterLockService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class TeacherAttendanceController extends Controller
{
    public function __construct(
        private QuarterLockService $quarterLockService
    ) {}

    /**
     * Display the take attendance page.
     */
    public function takeAttendance()
    {
        try {
            $userId = Auth::user()->id;
            $teacher = Teacher::where('user_id', $userId)->firstOrFail();
            $teacherId = $teacher->id;

            $activeSchoolYear = SchoolYear::active()->first();

            if (! $activeSchoolYear) {
                return redirect()->back()->with('error', 'Unable to load attendance page because no active school year is set.');
            }

            $activeQuarter = SchoolYearQuarter::where('school_year_id', $activeSchoolYear->id)
                ->current()
                ->first();

            $schedules = Schedule::with('class.section.gradeLevel', 'subject')
                ->where('teacher_id', $teacherId)
                ->whereHas('class', function ($query) use ($activeSchoolYear) {
                    $query->where('school_year_id', $activeSchoolYear->id);
                })
                ->get();

            $sections = $schedules->pluck('class')
                ->filter()
                ->unique('id')
                ->sortBy(function ($class) {
                    return [
                        (int) ($class->section?->gradeLevel?->level ?? 0),
                        (string) ($class->section?->name ?? ''),
                    ];
                })
                ->values();

            $sectionOptions = $sections->map(function ($class) use ($teacher) {
                $gradeLevel = (int) ($class->section?->gradeLevel?->level ?? 0);

                return [
                    'class_id' => (int) $class->id,
                    'section_id' => (int) ($class->section?->id ?? 0),
                    'section_name' => (string) ($class->section?->name ?? 'Unknown Section'),
                    'grade_level' => $gradeLevel,
                    'is_adviser' => (int) $class->teacher_id === (int) $teacher->id,
                ];
            })->values();

            return view('teacher.attendance.take', compact('sections', 'sectionOptions', 'teacherId', 'activeQuarter'));
        } catch (Throwable $e) {
            Log::error('TeacherAttendanceController@takeAttendance error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to load attendance page right now. Please try again.');
        }
    }

    /**
     * Display attendance records.
     */
    public function attendanceRecords()
    {
        try {
            $userId = Auth::id();
            $teacher = Teacher::where('user_id', $userId)->firstOrFail();
            $teacherId = $teacher->id;
            $activeSchoolYear = SchoolYear::active()->first();

            if (! $activeSchoolYear) {
                return redirect()->back()->with('error', 'No active school year found.');
            }

            $attendanceRecords = Attendance::where('attendances.teacher_id', $teacherId)
                ->where('attendances.school_year_id', $activeSchoolYear->id)
                ->leftJoin('student_profiles', 'attendances.student_profile_id', '=', 'student_profiles.id')
                ->join('students', DB::raw('COALESCE(attendances.student_id, student_profiles.student_id)'), '=', 'students.id')
                ->join('classes', 'attendances.class_id', '=', 'classes.id')
                ->join('sections', 'classes.section_id', '=', 'sections.id')
                ->join('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id')
                ->join('subjects', 'attendances.subject_id', '=', 'subjects.id')
                ->select(
                    'attendances.id',
                    'attendances.date',
                    'attendances.status',
                    'students.first_name',
                    'students.last_name',
                    'sections.name as section_name',
                    'grade_levels.name as grade_level_name',
                    'subjects.name as subject_name',
                    'attendances.class_id'
                )
                ->orderBy('attendances.date', 'desc')
                ->orderBy('students.last_name', 'asc')
                ->limit(500)
                ->get();

            $teacherSchedules = Schedule::where('teacher_id', $teacherId)
                ->whereHas('class', function ($q) use ($activeSchoolYear) {
                    $q->where('school_year_id', $activeSchoolYear->id);
                })
                ->with(['class.section.gradeLevel', 'subject'])
                ->get();

            $gradeLevels = $teacherSchedules->pluck('class.section.gradeLevel')->filter()->unique('id')->values();
            $sections = $teacherSchedules->pluck('class.section')->filter()->unique('id')->values();
            $subjects = $teacherSchedules->pluck('subject')->filter()->unique('id')->values();

            $teacherClasses = Classes::where('teacher_id', $teacherId)
                ->with('section')
                ->get();

            $defaultSummaryDateTo = Carbon::today()->toDateString();
            $defaultSummaryDateFrom = Carbon::today()->subDays(13)->toDateString();

            return view('teacher.attendance.records', compact(
                'gradeLevels',
                'subjects',
                'sections',
                'attendanceRecords',
                'teacherClasses',
                'defaultSummaryDateFrom',
                'defaultSummaryDateTo'
            ));
        } catch (Throwable $e) {
            Log::error('TeacherAttendanceController@attendanceRecords error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to load attendance records: '.$e->getMessage());
        }
    }

    /**
     * Get students for a section.
     */
    public function getStudents(Request $request)
    {
        try {
            $request->validate([
                'section_id' => 'required|integer',
                'subject_id' => 'required',
                'date' => 'required|date',
            ]);

            $activeSchoolYear = SchoolYear::active()->first();
            if (! $activeSchoolYear) {
                return response()->json(['message' => 'No active school year found.'], 400);
            }

            $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
            $class = $this->resolveClassForSection((int) $request->input('section_id'), (int) $activeSchoolYear->id);

            if (! $class) {
                return $this->validationErrorResponse(
                    'The selected section is not available for the active school year.',
                    'section_id'
                );
            }

            $isAllDay = $request->input('subject_id') === 'all';
            $subject = null;
            $schedule = null;

            if ($isAllDay) {
                if (! $this->isAdviserForClass($teacher, $class)) {
                    return $this->validationErrorResponse(
                        'All-subject attendance is only allowed for your advisory class.',
                        'subject_id'
                    );
                }
            } else {
                $subjectId = (int) $request->input('subject_id');
                $subject = Subject::find($subjectId);

                if (! $subject) {
                    return $this->validationErrorResponse('The selected subject is invalid.', 'subject_id');
                }

                $schedule = Schedule::where([
                    'class_id' => $class->id,
                    'subject_id' => $subjectId,
                    'teacher_id' => $teacher->id,
                ])->first();

                if (! $schedule) {
                    return $this->validationErrorResponse(
                        'You are not scheduled to handle this subject for the selected section.',
                        'subject_id'
                    );
                }
            }

            $date = $request->input('date');

            $students = Student::whereIn('id', function ($query) use ($class, $activeSchoolYear) {
                $query->select('student_id')
                    ->from('enrollments')
                    ->where('class_id', $class->id)
                    ->where('school_year_id', $activeSchoolYear->id);
            })->orderBy('last_name')->orderBy('first_name')->get();

            $studentIds = $students->pluck('id')->toArray();
            $profileMap = \App\Models\StudentProfile::whereIn('student_id', $studentIds)
                ->where('school_year_id', $activeSchoolYear->id)
                ->pluck('id', 'student_id')
                ->toArray();

            $profileIds = array_values($profileMap);

            $existingAttendanceQuery = Attendance::where([
                'date' => $date,
                'class_id' => $class->id,
                'school_year_id' => $activeSchoolYear->id,
            ]);

            if (! $isAllDay && $subject) {
                $existingAttendanceQuery->where('subject_id', $subject->id);
            }

            $existingAttendanceQuery->where(function ($q) use ($studentIds, $profileIds) {
                $q->whereIn('student_id', $studentIds);
                if (! empty($profileIds)) {
                    $q->orWhereIn('student_profile_id', $profileIds);
                }
            });

            $existingAttendanceRows = $existingAttendanceQuery->get();

            $attendance = [];
            foreach ($existingAttendanceRows as $att) {
                $resolvedStudentId = $att->student_id;
                if (! $resolvedStudentId && $att->student_profile_id) {
                    $resolvedStudentId = \App\Models\StudentProfile::find($att->student_profile_id)?->student_id;
                }
                if ($resolvedStudentId) {
                    if (! isset($attendance[$resolvedStudentId])) {
                        $attendance[$resolvedStudentId] = [
                            'status' => $att->status,
                            'remarks' => $att->remarks,
                        ];
                    }
                }
            }

            $formattedStudents = [];
            foreach ($students as $student) {
                $formattedStudents[] = [
                    'id' => $student->id,
                    'student_id' => $student->student_id ?? $student->lrn ?? 'N/A',
                    'name' => $student->full_name ?? $student->name,
                    'gender' => $student->gender,
                    'attendance' => $attendance[$student->id] ?? null,
                ];
            }

            $warning = null;
            if ($isAllDay && $this->isGradeFourToSixClass($class)) {
                $warning = 'All-subject attendance marks all scheduled class subjects, including those not handled by you.';
            }

            return response()->json([
                'section' => $class->section,
                'class_id' => $class->id,
                'subject' => $isAllDay ? ['name' => 'All Scheduled Subjects', 'code' => 'MULTIPLE'] : $subject,
                'schedule' => $schedule,
                'students' => $formattedStudents,
                'warning' => $warning,
            ]);
        } catch (Throwable $e) {
            Log::error('TeacherAttendanceController@getStudents error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Unable to retrieve students right now. Please try again.'], 500);
        }
    }

    /**
     * Process QR code scan for attendance.
     */
    public function scanAttendance(Request $request)
    {
        try {
            $request->validate([
                'bar_code' => 'required|string',
                'section_id' => 'required',
                'subject_id' => 'required|exists:subjects,id',
                'date' => 'required|date',
            ]);

            $barCode = $request->input('bar_code');
            $student = Student::where('bar_code', $barCode)
                ->orWhere('lrn', $barCode)
                ->first();

            if (! $student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found with this QR code',
                ], 404);
            }

            if ($student->section_id != $request->input('section_id')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student is not in the selected section',
                ], 400);
            }

            $activeSchoolYear = SchoolYear::active()->first();

            if (! $activeSchoolYear) {
                return response()->json(['message' => 'No active school year found.'], 400);
            }

            $attendance = Attendance::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'subject_id' => $request->subject_id,
                    'date' => $request->date,
                    'school_year_id' => $activeSchoolYear->id,
                ],
                [
                    'status' => 'present',
                    'teacher_id' => Auth::id(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Attendance recorded successfully',
                'student_name' => $student->full_name,
                'student_id' => $student->id,
            ]);
        } catch (Throwable $e) {
            Log::error('TeacherAttendanceController@scanAttendance error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Unable to record attendance: '.$e->getMessage()], 500);
        }
    }

    /**
     * Save attendance for multiple students.
     */
    public function saveAttendance(Request $request)
    {
        try {
            $validated = $request->validate([
                'section_id' => 'required|integer',
                'subject_id' => 'required',
                'date' => 'required|date',
                'quarter' => 'required|string',
                'status' => 'required|array',
                'remarks' => 'nullable|array',
            ]);

            $userId = Auth::id();
            $teacher = Teacher::where('user_id', $userId)->firstOrFail();
            $teacherId = $teacher->id;
            $activeSchoolYear = SchoolYear::active()->first();

            if (! $activeSchoolYear) {
                return response()->json(['message' => 'No active school year found.'], 400);
            }

            $class = $this->resolveClassForSection((int) $validated['section_id'], (int) $activeSchoolYear->id);
            if (! $class) {
                return $this->validationErrorResponse(
                    'The selected section is not available for the active school year.',
                    'section_id'
                );
            }

            $isAllDay = $validated['subject_id'] === 'all';
            $isAdviser = $this->isAdviserForClass($teacher, $class);

            if ($isAllDay && ! $isAdviser) {
                return $this->validationErrorResponse(
                    'All-subject attendance is only allowed for your advisory class.',
                    'subject_id'
                );
            }

            $quarterNumber = (int) filter_var($request->input('quarter'), FILTER_SANITIZE_NUMBER_INT);

            if ($this->quarterLockService->isLocked((int) $activeSchoolYear->id, $quarterNumber)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This quarter is locked. Attendance changes are disabled.',
                ], 423);
            }

            $subjectIds = [];
            if ($isAllDay) {
                $subjectIds = $this->resolveAllDaySubjectIdsForClass($class, $validated['date']);
            } else {
                $subjectId = (int) $validated['subject_id'];
                $subjectExists = Subject::where('id', $subjectId)->exists();

                if (! $subjectExists) {
                    return $this->validationErrorResponse('The selected subject is invalid.', 'subject_id');
                }

                $isTeacherScheduledForSubject = Schedule::where('class_id', $class->id)
                    ->where('subject_id', $subjectId)
                    ->where('teacher_id', $teacherId)
                    ->exists();

                if (! $isTeacherScheduledForSubject) {
                    return $this->validationErrorResponse(
                        'You are not scheduled to handle this subject for the selected section.',
                        'subject_id'
                    );
                }

                $subjectIds = [$subjectId];
            }

            if (empty($subjectIds)) {
                return response()->json(['message' => 'No scheduled subjects found for the selected class.'], 400);
            }

            $statusArray = $request->input('status', []);
            $remarksArray = $request->input('remarks', []);
            $enrolledStudentIds = Enrollment::query()
                ->where('class_id', $class->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->pluck('student_id')
                ->map(fn ($studentId) => (int) $studentId)
                ->all();

            $validStudentLookup = array_fill_keys($enrolledStudentIds, true);
            $studentProfileIdByStudentId = \App\Models\StudentProfile::query()
                ->whereIn('student_id', $enrolledStudentIds)
                ->where('school_year_id', $activeSchoolYear->id)
                ->pluck('id', 'student_id')
                ->map(fn ($profileId) => (int) $profileId)
                ->all();

            foreach ($statusArray as $studentId => $status) {
                if (! is_numeric($studentId)) {
                    continue;
                }

                $studentId = (int) $studentId;
                if (! isset($validStudentLookup[$studentId])) {
                    continue;
                }

                $remarks = $remarksArray[$studentId] ?? null;
                $studentProfileId = $studentProfileIdByStudentId[$studentId] ?? null;

                foreach ($subjectIds as $subjectId) {
                    Attendance::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'subject_id' => $subjectId,
                            'class_id' => $class->id,
                            'date' => $validated['date'],
                            'quarter' => $validated['quarter'],
                            'school_year_id' => $activeSchoolYear->id,
                        ],
                        [
                            'status' => $status,
                            'remarks' => $remarks,
                            'teacher_id' => $teacherId,
                            'student_profile_id' => $studentProfileId,
                        ]
                    );
                }

                $this->checkAbsences($studentId, $teacherId);
            }

            $responsePayload = [
                'success' => true,
                'message' => 'Attendance saved successfully for '.(count($subjectIds)).' subjects.',
            ];

            if ($isAllDay && $isAdviser && $this->isGradeFourToSixClass($class)) {
                $responsePayload['warning'] = 'All-subject attendance marks all scheduled class subjects, including those not handled by you.';
            }

            return response()->json($responsePayload);
        } catch (Throwable $e) {
            Log::error('TeacherAttendanceController@saveAttendance error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Unable to save attendance right now. Please try again.'], 500);
        }
    }

    /**
     * Update a single attendance record.
     */
    public function updateAttendance(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => ['required', Rule::in(['present', 'late', 'absent', 'excused'])],
            ]);

            $attendance = Attendance::findOrFail($id);

            if ($this->quarterLockService->isLocked((int) $attendance->school_year_id, (int) $attendance->quarter)) {
                return redirect()->route('teacher.attendance.records')->with('error', 'This quarter is locked. Attendance changes are disabled.');
            }

            $attendance->status = $request->input('status');
            $attendance->save();

            return redirect()->route('teacher.attendance.records')->with('success', 'Attendance record updated successfully.');
        } catch (Throwable $e) {
            Log::error('TeacherAttendanceController@updateAttendance error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->route('teacher.attendance.records')->with('error', 'Unable to update attendance: '.$e->getMessage());
        }
    }

    /**
     * Delete a single attendance record.
     */
    public function destroyAttendance($id)
    {
        try {
            $attendance = Attendance::findOrFail($id);

            if ($this->quarterLockService->isLocked((int) $attendance->school_year_id, (int) $attendance->quarter)) {
                return redirect()->route('teacher.attendance.records')->with('error', 'This quarter is locked. Attendance changes are disabled.');
            }

            $attendance->delete();

            return redirect()->route('teacher.attendance.records')->with('success', 'Attendance record deleted successfully.');
        } catch (Throwable $e) {
            Log::error('TeacherAttendanceController@destroyAttendance error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->route('teacher.attendance.records')->with('error', 'Unable to delete attendance: '.$e->getMessage());
        }
    }

    /**
     * Delete attendance record.
     */
    public function deleteAttendanceRecord($id)
    {
        try {
            $userId = Auth::id();
            $teacherId = Teacher::where('user_id', $userId)->value('id');

            $recordToDelete = Attendance::findOrFail($id);

            $date = $recordToDelete->date;
            $subjectId = $recordToDelete->subject_id;
            $studentIds = Student::where('section_id', $recordToDelete->student->section_id)->pluck('id')->toArray();

            $profileIds = \App\Models\StudentProfile::whereIn('student_id', $studentIds)
                ->where('school_year_id', $recordToDelete->school_year_id)
                ->pluck('id')
                ->toArray();

            $deletedCount = Attendance::where('attendances.date', $date)
                ->where('attendances.subject_id', $subjectId)
                ->where('attendances.teacher_id', $teacherId)
                ->where('attendances.school_year_id', $recordToDelete->school_year_id)
                ->where(function ($q) use ($studentIds, $profileIds) {
                    $q->whereIn('attendances.student_id', $studentIds);
                    if (! empty($profileIds)) {
                        $q->orWhereIn('attendances.student_profile_id', $profileIds);
                    }
                })
                ->delete();

            return redirect()->route('teacher.attendance.records')
                ->with('success', "Attendance record deleted successfully. {$deletedCount} entries were removed.");
        } catch (Throwable $e) {
            Log::error('TeacherAttendanceController@deleteAttendanceRecord error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->route('teacher.attendance.records')->with('error', 'Unable to delete attendance record: '.$e->getMessage());
        }
    }

    /**
     * Get attendance summary data.
     */
    public function getAttendanceSummary(Request $request)
    {
        try {
            $activeSchoolYear = SchoolYear::active()->first();
            if (! $activeSchoolYear) {
                return response()->json(['success' => false, 'message' => 'No active school year found.'], 400);
            }

            $classId = $request->input('class_id');
            $subjectId = $request->input('subject_id');
            $dateToInput = $request->input('date_to');
            $dateFromInput = $request->input('date_from');

            $dateTo = $dateToInput ? Carbon::parse($dateToInput)->toDateString() : Carbon::today()->toDateString();
            $dateFrom = $dateFromInput ? Carbon::parse($dateFromInput)->toDateString() : Carbon::parse($dateTo)->subDays(13)->toDateString();

            if ($dateFrom > $dateTo) {
                $dateFrom = Carbon::parse($dateTo)->subDays(13)->toDateString();
            }

            $attendanceQuery = Attendance::where('class_id', $classId)
                ->where('school_year_id', $activeSchoolYear->id)
                ->whereBetween('date', [$dateFrom, $dateTo]);

            if ($subjectId !== 'all') {
                $attendanceQuery->where('subject_id', $subjectId);
            }

            $stats = [
                'present_count' => (clone $attendanceQuery)->where('status', 'present')->count(),
                'late_count' => (clone $attendanceQuery)->where('status', 'late')->count(),
                'absent_count' => (clone $attendanceQuery)->where('status', 'absent')->count(),
                'excused_count' => (clone $attendanceQuery)->where('status', 'excused')->count(),
            ];

            $studentAttendanceQuery = Attendance::where('attendances.class_id', $classId)
                ->where('attendances.school_year_id', $activeSchoolYear->id)
                ->whereBetween('attendances.date', [$dateFrom, $dateTo]);

            if ($subjectId !== 'all') {
                $studentAttendanceQuery->where('attendances.subject_id', $subjectId);
            }

            $studentDetails = $studentAttendanceQuery
                ->join('students', 'attendances.student_id', '=', 'students.id')
                ->select(
                    'students.first_name',
                    'students.last_name',
                    DB::raw("SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as present_count"),
                    DB::raw("SUM(CASE WHEN attendances.status = 'late' THEN 1 ELSE 0 END) as late_count"),
                    DB::raw("SUM(CASE WHEN attendances.status = 'absent' THEN 1 ELSE 0 END) as absent_count")
                )
                ->groupBy('attendances.student_id', 'students.first_name', 'students.last_name')
                ->get()
                ->map(fn ($row) => [
                    'first_name' => $row->first_name,
                    'last_name' => $row->last_name,
                    'present_count' => (int) $row->present_count,
                    'late_count' => (int) $row->late_count,
                    'absent_count' => (int) $row->absent_count,
                    'is_at_risk' => (int) $row->absent_count >= 3,
                ])
                ->values()
                ->toArray();

            $trendDataQuery = Attendance::where('class_id', $classId)
                ->where('school_year_id', $activeSchoolYear->id)
                ->whereBetween('date', [$dateFrom, $dateTo]);

            if ($subjectId !== 'all') {
                $trendDataQuery->where('subject_id', $subjectId);
            }

            $trendData = $trendDataQuery->select(
                'date',
                DB::raw('count(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent")
            )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return response()->json([
                'stats' => $stats,
                'student_details' => $studentDetails,
                'trend_data' => $trendData,
            ]);
        } catch (Throwable $e) {
            Log::error('TeacherAttendanceController@getAttendanceSummary error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Unable to retrieve summary: '.$e->getMessage()], 500);
        }
    }

    // Private helper methods

    private function resolveClassForSection(int $sectionId, int $schoolYearId): ?Classes
    {
        if ($sectionId <= 0 || $schoolYearId <= 0) {
            return null;
        }

        return Classes::query()
            ->with('section.gradeLevel')
            ->where('section_id', $sectionId)
            ->where('school_year_id', $schoolYearId)
            ->first();
    }

    private function isAdviserForClass(Teacher $teacher, Classes $class): bool
    {
        return (int) $class->teacher_id === (int) $teacher->id;
    }

    private function isGradeFourToSixClass(Classes $class): bool
    {
        $gradeLevel = (int) ($class->section?->gradeLevel?->level ?? 0);

        return $gradeLevel >= 4 && $gradeLevel <= 6;
    }

    private function resolveAllDaySubjectIdsForClass(Classes $class, string $date): array
    {
        return Schedule::query()
            ->where('class_id', $class->id)
            ->pluck('subject_id')
            ->map(fn ($subjectId) => (int) $subjectId)
            ->unique()
            ->values()
            ->all();
    }

    private function validationErrorResponse(string $message, string $field): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => [
                $field => [$message],
            ],
        ], 422);
    }

    private function checkAbsences($studentId, $teacherId)
    {
        $threeDaysAgo = now()->subDays(2)->toDateString();
        $today = now()->toDateString();

        $absentDays = Attendance::where('student_id', $studentId)
            ->whereBetween('date', [$threeDaysAgo, $today])
            ->where('status', 'absent')
            ->distinct('date')
            ->count('date');

        $consecutiveAbsences = $absentDays;

        if ($consecutiveAbsences >= 3) {
            $cacheKey = 'absent_alert_sent_'.$studentId;
            $lastSent = cache($cacheKey);
            if (! $lastSent || now()->diffInHours($lastSent) >= 24) {
                try {
                    $student = Student::find($studentId);
                    $teacher = Teacher::with('user')->find($teacherId);

                    if ($teacher && $teacher->user && ! empty($teacher->user->email)) {
                        Mail::to($teacher->user->email)->queue(new AbsentAlertMail($student, $teacher, $consecutiveAbsences));
                    }

                    $guardian = $student->guardian ?? null;
                    $guardianUser = $guardian?->user;
                    if ($guardianUser && ! empty($guardianUser->email)) {
                        $guardianEmail = $guardianUser->email;
                        $teacherEmail = $teacher->user->email ?? null;
                        if ($guardianEmail !== $teacherEmail) {
                            Mail::to($guardianEmail)->queue(new AbsentAlertMail($student, $teacher, $consecutiveAbsences));
                        }
                    }
                } catch (Throwable $e) {
                    Log::error('Error sending absent alert: '.$e->getMessage(), ['student_id' => $studentId, 'exception' => $e]);
                }

                cache([$cacheKey => now()], now()->addHours(24));
            }
        }
    }
}
