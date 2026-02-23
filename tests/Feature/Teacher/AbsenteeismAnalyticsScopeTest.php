<?php

namespace Tests\Feature\Teacher;

use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class AbsenteeismAnalyticsScopeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Cache::flush();
        SchoolYear::query()->update(['is_active' => false]);
        $this->ensureTeacherRole();
    }

    public function test_teacher_absenteeism_view_only_contains_advisee_students(): void
    {
        $suffix = Str::lower(Str::random(8));
        $schoolYear = $this->createActiveSchoolYear($suffix);
        $gradeLevel = $this->createGradeLevel($suffix, 5);

        $sectionA = Section::create([
            'name' => 'SEC-A-'.$suffix,
            'grade_level_id' => $gradeLevel->id,
            'description' => 'Teacher A section',
        ]);
        $sectionB = Section::create([
            'name' => 'SEC-B-'.$suffix,
            'grade_level_id' => $gradeLevel->id,
            'description' => 'Teacher B section',
        ]);

        [$teacherAUser, $teacherA] = $this->createTeacherUser();
        [, $teacherB] = $this->createTeacherUser();

        $classA = Classes::create([
            'section_id' => $sectionA->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacherA->id,
            'capacity' => 30,
        ]);
        $classB = Classes::create([
            'section_id' => $sectionB->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacherB->id,
            'capacity' => 30,
        ]);

        $advisee = $this->createStudent('Advisee', 'One', $suffix);
        $nonAdvisee = $this->createStudent('NonAdvisee', 'Two', $suffix);

        $this->enrollStudent($advisee, $classA, $schoolYear);
        $this->enrollStudent($nonAdvisee, $classB, $schoolYear);

        $this->fakePredictionApi(
            table1Rows: [
                ['Student_ID' => $advisee->id, 'Name' => 'Advisee One', 'Prob_HighRisk_pct' => 85],
                ['Student_ID' => $nonAdvisee->id, 'Name' => 'NonAdvisee Two', 'Prob_HighRisk_pct' => 90],
            ],
            table2Rows: [
                [
                    'Student_ID' => $advisee->id,
                    'Name' => 'Advisee One',
                    'EngagementScore' => 91,
                    'PerformancePercentage' => 93,
                    'AttendancePercentage' => 96,
                    'Strength' => 'Math (95%)',
                    'Weakness' => 'Science (81%)',
                ],
                [
                    'Student_ID' => $nonAdvisee->id,
                    'Name' => 'NonAdvisee Two',
                    'EngagementScore' => 88,
                    'PerformancePercentage' => 94,
                    'AttendancePercentage' => 97,
                    'Strength' => 'English (93%)',
                    'Weakness' => 'AP (80%)',
                ],
            ],
            table3Rows: [
                [
                    'Student_ID' => $advisee->id,
                    'Name' => 'Advisee One',
                    'Prob_HighRisk_pct' => 86,
                    'Att_Current' => 84,
                    'Att_Past1' => 95,
                    'Att_Past2' => 94,
                    'Weighted_Trend' => -8,
                    'Performance_Trend' => -5,
                ],
                [
                    'Student_ID' => $nonAdvisee->id,
                    'Name' => 'NonAdvisee Two',
                    'Prob_HighRisk_pct' => 91,
                    'Att_Current' => 83,
                    'Att_Past1' => 96,
                    'Att_Past2' => 96,
                    'Weighted_Trend' => -9,
                    'Performance_Trend' => -4,
                ],
            ]
        );

        $response = $this->actingAs($teacherAUser)->get(route('teacher.analytics.absenteeism'));

        $response->assertOk();
        $featureTables = $response->viewData('featureTables');
        $this->assertSame([$advisee->id], $this->extractStudentIds($featureTables['table1']['data'] ?? []));
        $this->assertSame([$advisee->id], $this->extractStudentIds($featureTables['table2']['data'] ?? []));
        $this->assertSame([$advisee->id], $this->extractStudentIds($featureTables['table3']['data'] ?? []));

        $recognitionTop5 = $response->viewData('recognitionTop5');
        $this->assertCount(1, $recognitionTop5);
        $this->assertSame($advisee->id, (int) ($recognitionTop5[0]['Student_ID'] ?? 0));
    }

    public function test_honors_classification_thresholds_are_applied(): void
    {
        [$teacherUser, $schoolYear, $class] = $this->createScopedClassContext('honors', 6);

        $studentHigh = $this->createStudent('High', 'Honor', 'hon');
        $studentHonors = $this->createStudent('With', 'Honors', 'hon');
        $studentRegular = $this->createStudent('Regular', 'Student', 'hon');

        $this->enrollStudent($studentHigh, $class, $schoolYear);
        $this->enrollStudent($studentHonors, $class, $schoolYear);
        $this->enrollStudent($studentRegular, $class, $schoolYear);

        $this->fakePredictionApi(
            table2Rows: [
                [
                    'Student_ID' => $studentHigh->id,
                    'Name' => 'High Honor',
                    'EngagementScore' => 94,
                    'PerformancePercentage' => 95,
                    'AttendancePercentage' => 95,
                    'Strength' => 'Math (97%)',
                    'Weakness' => 'Science (90%)',
                ],
                [
                    'Student_ID' => $studentHonors->id,
                    'Name' => 'With Honors',
                    'EngagementScore' => 90,
                    'PerformancePercentage' => 90,
                    'AttendancePercentage' => 95,
                    'Strength' => 'English (95%)',
                    'Weakness' => 'Science (88%)',
                ],
                [
                    'Student_ID' => $studentRegular->id,
                    'Name' => 'Regular Student',
                    'EngagementScore' => 82,
                    'PerformancePercentage' => 89,
                    'AttendancePercentage' => 95,
                    'Strength' => 'AP (92%)',
                    'Weakness' => 'Science (83%)',
                ],
            ]
        );

        $response = $this->actingAs($teacherUser)->get(route('teacher.analytics.absenteeism'));

        $response->assertOk();
        $engagementRows = $response->viewData('featureTables')['table2']['data'] ?? [];
        $rowByStudentId = collect($engagementRows)->keyBy(fn ($row) => (int) ($row['Student_ID'] ?? 0));

        $this->assertSame('With High Honors', $rowByStudentId[$studentHigh->id]['HonorsClassification']);
        $this->assertSame('With Honors', $rowByStudentId[$studentHonors->id]['HonorsClassification']);
        $this->assertSame('Regular', $rowByStudentId[$studentRegular->id]['HonorsClassification']);

        $honorsSummary = $response->viewData('honorsSummary');
        $this->assertSame(1, (int) ($honorsSummary['with_high_honors_count'] ?? 0));
        $this->assertSame(1, (int) ($honorsSummary['with_honors_count'] ?? 0));
        $this->assertSame(1, (int) ($honorsSummary['regular_count'] ?? 0));
    }

    public function test_top_five_is_sorted_by_engagement_then_performance_then_name(): void
    {
        [$teacherUser, $schoolYear, $class] = $this->createScopedClassContext('topfive', 4);

        $students = [
            $this->createStudent('Zoey', 'Stone', 'tp1'),
            $this->createStudent('Aaron', 'Stone', 'tp2'),
            $this->createStudent('Mia', 'Stone', 'tp3'),
            $this->createStudent('Liam', 'Stone', 'tp4'),
            $this->createStudent('Noah', 'Stone', 'tp5'),
            $this->createStudent('Ivy', 'Stone', 'tp6'),
        ];

        foreach ($students as $student) {
            $this->enrollStudent($student, $class, $schoolYear);
        }

        $rows = [
            ['student' => $students[0], 'name' => 'Zoey Stone', 'eng' => 95.0, 'perf' => 85.0],
            ['student' => $students[1], 'name' => 'Aaron Stone', 'eng' => 95.0, 'perf' => 85.0],
            ['student' => $students[2], 'name' => 'Mia Stone', 'eng' => 93.0, 'perf' => 88.0],
            ['student' => $students[3], 'name' => 'Liam Stone', 'eng' => 91.0, 'perf' => 90.0],
            ['student' => $students[4], 'name' => 'Noah Stone', 'eng' => 90.0, 'perf' => 92.0],
            ['student' => $students[5], 'name' => 'Ivy Stone', 'eng' => 89.0, 'perf' => 93.0],
        ];

        $this->fakePredictionApi(
            table2Rows: array_map(function ($item) {
                return [
                    'Student_ID' => $item['student']->id,
                    'Name' => $item['name'],
                    'EngagementScore' => $item['eng'],
                    'PerformancePercentage' => $item['perf'],
                    'AttendancePercentage' => 96,
                    'Strength' => 'Math (95%)',
                    'Weakness' => 'Science (80%)',
                ];
            }, $rows)
        );

        $response = $this->actingAs($teacherUser)->get(route('teacher.analytics.absenteeism'));

        $response->assertOk();
        $top5 = $response->viewData('recognitionTop5');
        $top5Names = array_map(fn ($row) => (string) ($row['Name'] ?? ''), $top5);

        $this->assertSame([
            'Aaron Stone',
            'Zoey Stone',
            'Mia Stone',
            'Liam Stone',
            'Noah Stone',
        ], $top5Names);
    }

    public function test_intervention_and_declining_thresholds_are_applied(): void
    {
        [$teacherUser, $schoolYear, $class] = $this->createScopedClassContext('trends', 5);

        $criticalStudent = $this->createStudent('Critical', 'Case', 'tr1');
        $warningStudent = $this->createStudent('Warning', 'Case', 'tr2');
        $stableStudent = $this->createStudent('Stable', 'Case', 'tr3');

        $this->enrollStudent($criticalStudent, $class, $schoolYear);
        $this->enrollStudent($warningStudent, $class, $schoolYear);
        $this->enrollStudent($stableStudent, $class, $schoolYear);

        $this->fakePredictionApi(
            table3Rows: [
                [
                    'Student_ID' => $criticalStudent->id,
                    'Name' => 'Critical Case',
                    'Prob_HighRisk_pct' => 90,
                    'Att_Current' => 84,
                    'Att_Past1' => 95,
                    'Att_Past2' => 94,
                    'Weighted_Trend' => -7,
                    'Performance_Trend' => -4,
                ],
                [
                    'Student_ID' => $warningStudent->id,
                    'Name' => 'Warning Case',
                    'Prob_HighRisk_pct' => 50,
                    'Att_Current' => 90,
                    'Att_Past1' => 95,
                    'Att_Past2' => 93,
                    'Weighted_Trend' => -4,
                    'Performance_Trend' => -2,
                ],
                [
                    'Student_ID' => $stableStudent->id,
                    'Name' => 'Stable Case',
                    'Prob_HighRisk_pct' => 10,
                    'Att_Current' => 92,
                    'Att_Past1' => 96,
                    'Att_Past2' => 95,
                    'Weighted_Trend' => -1,
                    'Performance_Trend' => 1,
                ],
            ]
        );

        $response = $this->actingAs($teacherUser)->get(route('teacher.analytics.absenteeism'));

        $response->assertOk();
        $decliningRows = $response->viewData('decliningTrendRows');
        $interventionQueue = $response->viewData('interventionQueue');

        $this->assertCount(2, $decliningRows);
        $this->assertCount(2, $interventionQueue);

        $decliningByStudent = collect($decliningRows)->keyBy('student_id');
        $this->assertSame('critical', $decliningByStudent[$criticalStudent->id]['severity']);
        $this->assertSame('warning', $decliningByStudent[$warningStudent->id]['severity']);
        $this->assertArrayNotHasKey($stableStudent->id, $decliningByStudent->all());

        $queueByStudent = collect($interventionQueue)->keyBy('student_id');
        $this->assertSame(
            'Schedule counseling and guardian contact within this week.',
            $queueByStudent[$criticalStudent->id]['recommended_action']
        );
        $this->assertSame(
            'Set a monitoring check-in and attendance follow-up.',
            $queueByStudent[$warningStudent->id]['recommended_action']
        );
        $this->assertArrayNotHasKey($stableStudent->id, $queueByStudent->all());
    }

    public function test_python_risk_label_and_raw_probability_are_used_for_display(): void
    {
        [$teacherUser, $schoolYear, $class] = $this->createScopedClassContext('cal', 6);

        $students = [
            $this->createStudent('One', 'Risk', 'cal1'),
            $this->createStudent('Two', 'Risk', 'cal2'),
            $this->createStudent('Three', 'Risk', 'cal3'),
            $this->createStudent('Four', 'Risk', 'cal4'),
        ];

        foreach ($students as $student) {
            $this->enrollStudent($student, $class, $schoolYear);
        }

        $rawScores = [12.0, 27.0, 73.0, 94.0];
        $riskLabels = ['Low', 'Mid', 'High', 'High'];
        $table1Rows = [];
        foreach ($students as $index => $student) {
            $table1Rows[] = [
                'Student_ID' => $student->id,
                'Name' => $student->first_name.' '.$student->last_name,
                'Prob_HighRisk_pct' => $rawScores[$index],
                'Risk_Label' => $riskLabels[$index],
            ];
        }

        $this->fakePredictionApi(table1Rows: $table1Rows);

        $response = $this->actingAs($teacherUser)->get(route('teacher.analytics.absenteeism'));

        $response->assertOk();
        $rows = $response->viewData('featureTables')['table1']['data'] ?? [];
        $this->assertCount(4, $rows);

        $rowsByStudent = collect($rows)->keyBy(fn ($row) => (int) ($row['Student_ID'] ?? 0));

        for ($i = 0; $i < count($students); $i++) {
            $studentId = $students[$i]->id;
            $row = $rowsByStudent[$studentId];
            $expectedLabel = $riskLabels[$i] === 'Mid' ? 'Medium' : $riskLabels[$i];
            $expectedProb = round($rawScores[$i], 1);

            $this->assertSame($expectedProb, (float) ($row['raw_prob_highrisk_pct'] ?? -1));
            $this->assertSame($expectedProb, (float) ($row['display_prob_highrisk_pct'] ?? -1));
            $this->assertSame($expectedLabel, (string) ($row['Display_Risk_Label'] ?? ''));
        }

        $riskMeta = $response->viewData('riskCalibrationMeta');
        $this->assertSame('python_label_raw_probability', $riskMeta['method'] ?? null);
    }

    public function test_repeated_request_uses_cached_payload_and_avoids_duplicate_python_fetch(): void
    {
        [$teacherUser, $schoolYear, $class] = $this->createScopedClassContext('cache', 4);
        $student = $this->createStudent('Cache', 'Student', 'cac1');
        $this->enrollStudent($student, $class, $schoolYear);

        $featureRequestCount = 0;
        $this->fakePredictionApi(
            table1Rows: [
                ['Student_ID' => $student->id, 'Name' => 'Cache Student', 'Prob_HighRisk_pct' => 40],
            ],
            onFeaturesRequest: function () use (&$featureRequestCount): void {
                $featureRequestCount++;
            }
        );

        $firstResponse = $this->actingAs($teacherUser)->get(route('teacher.analytics.absenteeism'));
        $secondResponse = $this->actingAs($teacherUser)->get(route('teacher.analytics.absenteeism'));

        $firstResponse->assertOk();
        $secondResponse->assertOk();
        $this->assertSame(1, $featureRequestCount);
    }

    private function createScopedClassContext(string $suffix, int $gradeLevel): array
    {
        $schoolYear = $this->createActiveSchoolYear($suffix);
        $grade = $this->createGradeLevel($suffix, $gradeLevel);
        $section = Section::create([
            'name' => strtoupper('SEC-'.$suffix),
            'grade_level_id' => $grade->id,
            'description' => 'Section '.$suffix,
        ]);

        [$teacherUser, $teacher] = $this->createTeacherUser();
        $class = Classes::create([
            'section_id' => $section->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
            'capacity' => 30,
        ]);

        return [$teacherUser, $schoolYear, $class];
    }

    private function createGradeLevel(string $suffix, int $level): GradeLevel
    {
        return GradeLevel::create([
            'name' => 'Grade '.$level.' '.$suffix,
            'level' => $level,
            'description' => 'Grade level '.$suffix,
        ]);
    }

    private function createActiveSchoolYear(string $suffix): SchoolYear
    {
        return SchoolYear::create([
            'name' => 'SY-'.$suffix,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->addMonths(10)->endOfMonth()->toDateString(),
            'is_active' => true,
            'is_promotion_open' => false,
        ]);
    }

    private function createStudent(string $firstName, string $lastName, string $suffix): Student
    {
        return Student::create([
            'student_id' => strtoupper($suffix).'-'.Str::upper(Str::random(4)),
            'lrn' => fake()->unique()->numerify('###########'),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'gender' => fake()->randomElement(['male', 'female']),
            'birthdate' => '2015-01-01',
            'enrollment_date' => now()->toDateString(),
        ]);
    }

    private function enrollStudent(Student $student, Classes $class, SchoolYear $schoolYear): void
    {
        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'school_year_id' => $schoolYear->id,
            'status' => 'enrolled',
            'enrollment_date' => now()->toDateString(),
        ]);
    }

    private function ensureTeacherRole(): Role
    {
        return Role::firstOrCreate(
            ['name' => 'teacher'],
            ['description' => 'Teacher']
        );
    }

    private function createTeacherUser(): array
    {
        $role = $this->ensureTeacherRole();
        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'phone' => '09'.fake()->numerify('#########'),
            'gender' => fake()->randomElement(['male', 'female']),
            'date_of_birth' => '1990-01-01',
            'address' => 'Teacher address',
            'qualification' => 'Bachelor of Education',
            'status' => 'active',
        ]);

        return [$user, $teacher];
    }

    private function fakePredictionApi(
        array $table1Rows = [],
        array $table2Rows = [],
        array $table3Rows = [],
        ?callable $onFeaturesRequest = null
    ): void {
        Http::fake([
            'http://127.0.0.1:8001/features/tables' => function (ClientRequest $request) use (
                $table1Rows,
                $table2Rows,
                $table3Rows,
                $onFeaturesRequest
            ) {
                if ($onFeaturesRequest) {
                    $onFeaturesRequest($request);
                }

                return Http::response([
                    'success' => true,
                    'table1' => ['data' => $table1Rows],
                    'table2' => ['data' => $table2Rows],
                    'table3' => ['data' => $table3Rows],
                ], 200);
            },
            'http://127.0.0.1:8001/prediction_probability_batch' => Http::response([
                'predictions' => [
                    ['prediction_confidence' => 0.42, 'risk_label' => 'Moderate'],
                ],
            ], 200),
        ]);
    }

    private function extractStudentIds(array $rows): array
    {
        return array_values(array_map(fn ($row) => (int) ($row['Student_ID'] ?? 0), $rows));
    }
}
