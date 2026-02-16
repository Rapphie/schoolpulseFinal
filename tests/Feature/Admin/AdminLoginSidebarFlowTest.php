<?php

namespace Tests\Feature\Admin;

use App\Models\Classes;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\SchoolYear;
use App\Models\SchoolYearQuarter;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminLoginSidebarFlowTest extends TestCase
{
    use DatabaseTransactions;

    private User $adminUser;

    private SchoolYear $schoolYear;

    private Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seedEnvironment();
    }

    public function test_admin_login_flow_redirects_to_admin_dashboard(): void
    {
        $this->get(route('login'))
            ->assertStatus(200);

        $response = $this->post(route('authenticate'), [
            'email' => $this->adminUser->email,
            'password' => 'admin-password',
            'role' => '1',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($this->adminUser);

        $this->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_dashboard_sidebar_links_point_to_working_pages(): void
    {
        $sidebarRoutes = [
            'admin.dashboard',
            'admin.grade-levels.index',
            'admin.subjects.index',
            'admin.schedules.index',
            'admin.sections.index',
            'admin.teachers.index',
            'admin.reports.enrollees',
            'admin.reports.attendance',
            'admin.reports.grades',
            'admin.reports.cumulative',
            'admin.settings.index',
        ];

        $dashboardResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'));
        $dashboardResponse->assertStatus(200);

        foreach ($sidebarRoutes as $routeName) {
            $dashboardResponse->assertSee(route($routeName), false);

            $this->actingAs($this->adminUser)
                ->get(route($routeName))
                ->assertStatus(200);
        }
    }

    public function test_admin_section_show_uses_admin_class_record_routes_and_backends_are_accessible(): void
    {
        $sectionPage = $this->actingAs($this->adminUser)
            ->get(route('admin.sections.show', $this->section));
        $sectionPage->assertStatus(200);
        $sectionPage->assertSee(route('admin.class-record.upload'), false);
        $sectionPage->assertSee(route('admin.class-record.save'), false);
        $sectionPage->assertDontSee(route('teacher.class-record.upload'), false);
        $sectionPage->assertDontSee(route('teacher.class-record.save'), false);

        $uploadResponse = $this->actingAs($this->adminUser)
            ->postJson(route('admin.class-record.upload'), []);
        $uploadResponse->assertStatus(422)
            ->assertJsonValidationErrors(['class_record_file']);

        $saveResponse = $this->actingAs($this->adminUser)
            ->postJson(route('admin.class-record.save'), [
                'headerData' => ['grade_level' => 'Grade 4'],
                'maleStudents' => [
                    [
                        'first_name' => 'Import',
                        'last_name' => 'Student',
                        'lrn' => '123456789012',
                    ],
                ],
                'femaleStudents' => [],
            ]);

        $saveResponse->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('students', [
            'first_name' => 'Import',
            'last_name' => 'Student',
        ]);
    }

    private function seedEnvironment(): void
    {
        $suffix = Str::lower(Str::random(8));

        $this->ensureRole('admin', 1);
        $this->ensureRole('teacher', 2);
        $this->ensureRole('guardian', 3);

        $this->adminUser = User::factory()->create([
            'role_id' => 1,
            'password' => Hash::make('admin-password'),
            'temporary_password' => null,
        ]);

        $this->schoolYear = SchoolYear::create([
            'name' => '2099-2100-'.$suffix,
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'is_active' => true,
            'is_promotion_open' => true,
        ]);

        SchoolYearQuarter::create([
            'school_year_id' => $this->schoolYear->id,
            'quarter' => 1,
            'name' => 'First Quarter',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'grade_submission_deadline' => now()->endOfMonth()->toDateString(),
            'is_locked' => false,
            'is_manually_set_active' => true,
        ]);

        $gradeLevel = GradeLevel::create([
            'name' => 'Grade 4 '.$suffix,
            'level' => random_int(10, 99),
            'description' => 'Admin login sidebar flow test',
        ]);

        $this->section = Section::create([
            'name' => 'Section-'.$suffix,
            'grade_level_id' => $gradeLevel->id,
            'description' => 'Admin login sidebar flow section',
        ]);

        Classes::create([
            'section_id' => $this->section->id,
            'school_year_id' => $this->schoolYear->id,
            'teacher_id' => null,
            'capacity' => 40,
        ]);

        Cache::forget('sidebar_settings');
    }

    private function ensureRole(string $name, int $id): void
    {
        Role::firstOrCreate(
            ['id' => $id],
            ['name' => $name, 'description' => ucfirst($name)]
        );
    }
}
