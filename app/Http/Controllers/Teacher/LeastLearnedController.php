<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreLeastLearnedRequest;
use App\Models\Classes;
use App\Models\GradeLevel;
use App\Models\LLC;
use App\Models\LLCItem;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Section;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class LeastLearnedController extends Controller
{
    public function index(Request $request)
    {
        try {
            $teacher = optional(Auth::user())->teacher;
            if (!$teacher) {
                abort(403, 'Only teachers can access Least Learned records.');
            }

            $activeSchoolYear = SchoolYear::active()->first();

            $gradeLevels = GradeLevel::orderBy('level')->get();
            $quarters = $this->quarterOptions();

            $sections = Classes::with('section.gradeLevel')
                ->when($activeSchoolYear, function ($query) use ($activeSchoolYear) {
                    $query->where('school_year_id', $activeSchoolYear->id);
                })
                ->where(function ($query) use ($teacher) {
                    $query->where('teacher_id', $teacher->id)
                        ->orWhereHas('schedules', function ($scheduleQuery) use ($teacher) {
                            $scheduleQuery->where('teacher_id', $teacher->id);
                        });
                })
                ->get()
                ->pluck('section')
                ->filter()
                ->unique('id')
                ->values();

            $subjects = Schedule::with('subject')
                ->where('teacher_id', $teacher->id)
                ->when($activeSchoolYear, function ($query) use ($activeSchoolYear) {
                    $query->whereHas('class', function ($classQuery) use ($activeSchoolYear) {
                        $classQuery->where('school_year_id', $activeSchoolYear->id);
                    });
                })
                ->get()
                ->pluck('subject')
                ->filter()
                ->unique('id')
                ->values();

            $llcQuery = LLC::with(['subject', 'section.gradeLevel'])
                ->withCount('llcItems')
                ->where('teacher_id', $teacher->id)
                ->when($activeSchoolYear, function ($query) use ($activeSchoolYear) {
                    $query->where('school_year_id', $activeSchoolYear->id);
                });

            $filterSection = $request->input('filter_section');
            $filterSubject = $request->input('filter_subject');
            $filterQuarter = $request->input('filter_quarter');

            if ($filterSection) {
                $llcQuery->where('section_id', $filterSection);
            }

            if ($filterSubject) {
                $llcQuery->where('subject_id', $filterSubject);
            }

            if ($filterQuarter) {
                $llcQuery->where('quarter', (int) $filterQuarter);
            }

            /** @var LengthAwarePaginator $llcRecords */
            $llcRecords = $llcQuery
                ->orderByDesc('created_at')
                ->paginate(10)
                ->withQueryString();

            return view('teacher.least-learned.index', [
                'gradeLevels' => $gradeLevels,
                'quarters' => $quarters,
                'sections' => $sections,
                'subjects' => $subjects,
                'llcRecords' => $llcRecords,
                'filters' => [
                    'section' => $filterSection,
                    'subject' => $filterSubject,
                    'quarter' => $filterQuarter,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('LeastLearnedController@index failed', ['exception' => $e]);

            return redirect()
                ->route('teacher.dashboard')
                ->with('error', 'Unable to load least learned module right now.');
        }
    }

    public function store(StoreLeastLearnedRequest $request): RedirectResponse
    {
        $teacher = optional(Auth::user())->teacher;
        if (!$teacher) {
            abort(403, 'Only teachers can submit least learned reports.');
        }

        $activeSchoolYear = SchoolYear::active()->first();
        if (!$activeSchoolYear) {
            throw ValidationException::withMessages([
                'school_year_id' => 'No active school year configured. Please contact the administrator.',
            ]);
        }

        $section = Section::with('gradeLevel')->findOrFail($request->section_id);
        if ((int) $section->grade_level_id !== (int) $request->grade_level_id) {
            throw ValidationException::withMessages([
                'section_id' => 'Selected section does not belong to the chosen grade level.',
            ]);
        }

        $teachesSection = Classes::where('section_id', $section->id)
            ->where('school_year_id', $activeSchoolYear->id)
            ->where(function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id)
                    ->orWhereHas('schedules', function ($scheduleQuery) use ($teacher) {
                        $scheduleQuery->where('teacher_id', $teacher->id);
                    });
            })
            ->exists();

        if (!$teachesSection) {
            throw ValidationException::withMessages([
                'section_id' => 'You are not assigned to the selected section for the active school year.',
            ]);
        }

        $teachesSubject = Schedule::where('teacher_id', $teacher->id)
            ->where('subject_id', $request->subject_id)
            ->whereHas('class', function ($classQuery) use ($section, $activeSchoolYear) {
                $classQuery->where('section_id', $section->id)
                    ->where('school_year_id', $activeSchoolYear->id);
            })
            ->exists();

        if (!$teachesSubject) {
            throw ValidationException::withMessages([
                'subject_id' => 'You are not assigned to teach this subject for the selected section.',
            ]);
        }

        $normalizedCategories = $this->normalizeCategoriesPayload(
            $request->input('categories_payload'),
            (int) $request->total_items,
            (int) $request->total_students
        );

        $examTitle = $request->input('exam_title');
        if (!$examTitle) {
            $examTitle = sprintf(
                '%s - %s',
                $section->name,
                $this->quarterOptions()[(int) $request->quarter] ?? 'Assessment'
            );
        }

        try {
            $llc = DB::transaction(function () use ($request, $teacher, $activeSchoolYear, $normalizedCategories, $examTitle) {
                $llc = LLC::create([
                    'subject_id' => $request->subject_id,
                    'section_id' => $request->section_id,
                    'teacher_id' => $teacher->id,
                    'school_year_id' => $activeSchoolYear->id,
                    'quarter' => (int) $request->quarter,
                    'exam_title' => $examTitle,
                    'total_students' => (int) $request->total_students,
                    'total_items' => (int) $request->total_items,
                ]);

                $timestamp = now();
                $itemsPayload = [];
                foreach ($normalizedCategories as $category) {
                    foreach ($category['items'] as $itemNumber => $studentsWrong) {
                        $itemsPayload[] = [
                            'llc_id' => $llc->id,
                            'item_number' => $itemNumber,
                            'students_wrong' => $studentsWrong,
                            'category_name' => $category['name'],
                            'item_start' => $category['start'],
                            'item_end' => $category['end'],
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ];
                    }
                }

                LLCItem::insert($itemsPayload);

                return $llc;
            });
        } catch (Throwable $e) {
            Log::error('LeastLearnedController@store failed', ['exception' => $e]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Unable to save least learned data. Please try again.');
        }

        return redirect()
            ->route('teacher.least-learned.show', $llc)
            ->with('success', 'Least learned analysis saved successfully.');
    }

    public function show(LLC $llc)
    {
        $teacher = optional(Auth::user())->teacher;
        if (!$teacher || (int) $llc->teacher_id !== (int) $teacher->id) {
            abort(403, 'You are not allowed to view this record.');
        }

        $llc->load(['subject', 'section.gradeLevel', 'llcItems']);

        $totalStudents = max($llc->total_students, 1);
        $categorySummary = $llc->llcItems
            ->groupBy('category_name')
            ->map(function ($items) use ($totalStudents) {
                $first = $items->first();
                $totalWrong = $items->sum('students_wrong');
                $itemCount = max($items->count(), 1);
                $avgWrongPercentage = ($totalWrong / ($itemCount * $totalStudents)) * 100;

                return [
                    'category' => $first->category_name,
                    'item_start' => $first->item_start,
                    'item_end' => $first->item_end,
                    'total_wrong' => $totalWrong,
                    'avg_wrong_percentage' => round($avgWrongPercentage, 2),
                    'items' => $items->map(function ($item) use ($totalStudents) {
                        $mastery = (($totalStudents - $item->students_wrong) / $totalStudents) * 100;

                        return [
                            'item_number' => $item->item_number,
                            'students_wrong' => $item->students_wrong,
                            'mastery_rate' => round(max($mastery, 0), 2),
                        ];
                    })->values(),
                ];
            })
            ->values();

        return view('teacher.least-learned.show', [
            'llc' => $llc,
            'categorySummary' => $categorySummary,
            'quarters' => $this->quarterOptions(),
        ]);
    }

    private function normalizeCategoriesPayload(string $payload, int $totalItems, int $totalStudents): array
    {
        $decoded = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages([
                'categories_payload' => 'Submitted category data is not valid JSON.',
            ]);
        }

        if (isset($decoded['categories']) && is_array($decoded['categories'])) {
            $decoded = $decoded['categories'];
        }

        if (!is_array($decoded) || empty($decoded)) {
            throw ValidationException::withMessages([
                'categories_payload' => 'Please provide at least one category mapping.',
            ]);
        }

        $normalized = [];
        $coveredItems = [];

        foreach ($decoded as $category) {
            $name = trim((string) ($category['name'] ?? ''));
            $start = (int) ($category['start'] ?? 0);
            $end = (int) ($category['end'] ?? 0);
            $items = $category['items'] ?? [];

            if ($name === '' || $start <= 0 || $end < $start) {
                throw ValidationException::withMessages([
                    'categories_payload' => 'Each category must include a name and a valid item range.',
                ]);
            }

            $normalizedItems = [];
            if ($this->isAssoc($items)) {
                foreach ($items as $itemNumber => $value) {
                    $normalizedItems[(int) $itemNumber] = $this->sanitizeWrongCount($value, $totalStudents);
                }
            } else {
                foreach ($items as $itemEntry) {
                    $itemNumber = (int) ($itemEntry['item_number'] ?? $itemEntry['item'] ?? 0);
                    $value = $itemEntry['students_wrong'] ?? $itemEntry['value'] ?? null;
                    $normalizedItems[$itemNumber] = $this->sanitizeWrongCount($value, $totalStudents);
                }
            }

            for ($i = $start; $i <= $end; $i++) {
                if (!array_key_exists($i, $normalizedItems)) {
                    throw ValidationException::withMessages([
                        'categories_payload' => "Missing wrong-answer count for Item {$i} in {$name}.",
                    ]);
                }

                if (isset($coveredItems[$i])) {
                    throw ValidationException::withMessages([
                        'categories_payload' => "Item {$i} has been assigned to multiple categories.",
                    ]);
                }

                $coveredItems[$i] = true;
            }

            $normalized[] = [
                'name' => $name,
                'start' => $start,
                'end' => $end,
                'items' => $normalizedItems,
            ];
        }

        if (count($coveredItems) !== $totalItems) {
            $missing = [];
            for ($i = 1; $i <= $totalItems; $i++) {
                if (!isset($coveredItems[$i])) {
                    $missing[] = $i;
                }
            }

            throw ValidationException::withMessages([
                'categories_payload' => 'Item coverage mismatch. Missing: ' . implode(', ', $missing) . '.',
            ]);
        }

        return $normalized;
    }

    private function sanitizeWrongCount($value, int $totalStudents): int
    {
        $intValue = (int) $value;
        if ($intValue < 0) {
            $intValue = 0;
        }

        if ($intValue > $totalStudents) {
            $intValue = $totalStudents;
        }

        return $intValue;
    }

    private function isAssoc(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    private function quarterOptions(): array
    {
        return [
            1 => '1st Quarter',
            2 => '2nd Quarter',
            3 => '3rd Quarter',
            4 => '4th Quarter',
        ];
    }
}
