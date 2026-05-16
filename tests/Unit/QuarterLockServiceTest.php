<?php

namespace Tests\Unit;

use App\Models\SchoolYear;
use App\Models\SchoolYearQuarter;
use App\Services\QuarterLockService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class QuarterLockServiceTest extends TestCase
{
    use DatabaseTransactions;

    private QuarterLockService $service;

    private SchoolYear $schoolYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new QuarterLockService;

        QuarterLockService::clearCache();

        SchoolYear::query()->update(['is_active' => false]);
        SchoolYearQuarter::query()->update(['is_manually_set_active' => false, 'is_locked' => null]);

        $this->schoolYear = SchoolYear::create([
            'name' => '2026-2027-locktest',
            'start_date' => '2026-01-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
        ]);
    }

    private function createAllQuarters(): array
    {
        return [
            SchoolYearQuarter::create([
                'school_year_id' => $this->schoolYear->id,
                'quarter' => 1,
                'name' => 'First Quarter',
                'start_date' => '2026-01-01',
                'end_date' => '2026-03-31',
                'is_locked' => null,
                'is_manually_set_active' => false,
            ]),
            SchoolYearQuarter::create([
                'school_year_id' => $this->schoolYear->id,
                'quarter' => 2,
                'name' => 'Second Quarter',
                'start_date' => '2026-04-01',
                'end_date' => '2026-06-30',
                'is_locked' => null,
                'is_manually_set_active' => false,
            ]),
            SchoolYearQuarter::create([
                'school_year_id' => $this->schoolYear->id,
                'quarter' => 3,
                'name' => 'Third Quarter',
                'start_date' => '2026-07-01',
                'end_date' => '2026-09-30',
                'is_locked' => null,
                'is_manually_set_active' => false,
            ]),
            SchoolYearQuarter::create([
                'school_year_id' => $this->schoolYear->id,
                'quarter' => 4,
                'name' => 'Fourth Quarter',
                'start_date' => '2026-10-01',
                'end_date' => '2027-03-31',
                'is_locked' => null,
                'is_manually_set_active' => false,
            ]),
        ];
    }

    public function test_null_is_locked_auto_locks_past_quarters(): void
    {
        $quarters = $this->createAllQuarters();

        $quarters[1]->update(['is_manually_set_active' => true]);
        $quarters[0]->update(['is_manually_set_active' => false]);

        $context = $this->service->contextForSchoolYear($this->schoolYear->id);
        $lockInfo = $context['quarterLocks'];

        $this->assertTrue($lockInfo[1]['is_locked'], 'Q1 (past) should be auto-locked when is_locked=null');
        $this->assertEquals('Quarter Ended', $lockInfo[1]['lock_reason_label']);
        $this->assertFalse($lockInfo[1]['is_explicitly_locked']);
        $this->assertFalse($lockInfo[1]['is_explicitly_unlocked']);

        $this->assertFalse($lockInfo[2]['is_locked'], 'Q2 (active) should be unlocked when is_locked=null');
        $this->assertNull($lockInfo[2]['lock_reason_label']);
        $this->assertFalse($lockInfo[2]['is_explicitly_locked']);
        $this->assertFalse($lockInfo[2]['is_explicitly_unlocked']);
    }

    public function test_explicitly_unlocked_past_quarter_is_not_locked(): void
    {
        $quarters = $this->createAllQuarters();

        $quarters[1]->update(['is_manually_set_active' => true]);
        $quarters[0]->update(['is_locked' => false]);
        $quarters[0]->update(['is_manually_set_active' => false]);

        $context = $this->service->contextForSchoolYear($this->schoolYear->id);
        $lockInfo = $context['quarterLocks'];

        $this->assertFalse($lockInfo[1]['is_locked'], 'Q1 should be unlocked when is_locked=false (explicit unlock)');
        $this->assertNull($lockInfo[1]['lock_reason_label']);
        $this->assertFalse($lockInfo[1]['is_explicitly_locked']);
        $this->assertTrue($lockInfo[1]['is_explicitly_unlocked']);
    }

    public function test_explicitly_locked_quarter_is_always_locked(): void
    {
        $quarters = $this->createAllQuarters();

        $quarters[1]->update(['is_manually_set_active' => true, 'is_locked' => true]);

        $context = $this->service->contextForSchoolYear($this->schoolYear->id);
        $lockInfo = $context['quarterLocks'];

        $this->assertTrue($lockInfo[2]['is_locked'], 'Q2 should be locked when is_locked=true');
        $this->assertEquals('Locked by Admin', $lockInfo[2]['lock_reason_label']);
        $this->assertTrue($lockInfo[2]['is_explicitly_locked']);
        $this->assertFalse($lockInfo[2]['is_explicitly_unlocked']);
    }

    public function test_explicitly_unlocked_active_quarter_is_not_locked(): void
    {
        $quarters = $this->createAllQuarters();

        $quarters[1]->update(['is_manually_set_active' => true, 'is_locked' => false]);

        $context = $this->service->contextForSchoolYear($this->schoolYear->id);
        $lockInfo = $context['quarterLocks'];

        $this->assertFalse($lockInfo[2]['is_locked'], 'Active Q2 should be unlocked when is_locked=false');
        $this->assertTrue($lockInfo[2]['is_explicitly_unlocked']);
    }

    public function test_toggle_lock_sets_explicit_true_when_locking(): void
    {
        $quarters = $this->createAllQuarters();
        $quarters[1]->update(['is_manually_set_active' => true]);

        $quarters[1]->update(['is_locked' => true]);

        $quarters[1]->refresh();
        $this->assertTrue($quarters[1]->is_locked === true);
    }

    public function test_toggle_unlock_sets_explicit_false(): void
    {
        $quarters = $this->createAllQuarters();
        $quarters[1]->update(['is_manually_set_active' => true]);

        $quarters[1]->update(['is_locked' => true]);
        $quarters[1]->update(['is_locked' => false]);

        $quarters[1]->refresh();
        $this->assertSame(false, $quarters[1]->is_locked);

        $context = $this->service->contextForSchoolYear($this->schoolYear->id);
        $this->assertFalse($context['quarterLocks'][2]['is_locked']);
        $this->assertTrue($context['quarterLocks'][2]['is_explicitly_unlocked']);
    }

    public function test_new_quarter_has_null_is_locked(): void
    {
        $quarter = SchoolYearQuarter::create([
            'school_year_id' => $this->schoolYear->id,
            'quarter' => 1,
            'name' => 'First Quarter',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
        ]);

        $this->assertNull($quarter->fresh()->is_locked);
    }

    public function test_model_explicitly_locked_method(): void
    {
        $quarters = $this->createAllQuarters();

        $this->assertFalse($quarters[0]->isExplicitlyLocked());
        $this->assertFalse($quarters[0]->isExplicitlyUnlocked());

        $quarters[0]->update(['is_locked' => true]);
        $this->assertTrue($quarters[0]->fresh()->isExplicitlyLocked());
        $this->assertFalse($quarters[0]->fresh()->isExplicitlyUnlocked());

        $quarters[0]->update(['is_locked' => false]);
        $this->assertFalse($quarters[0]->fresh()->isExplicitlyLocked());
        $this->assertTrue($quarters[0]->fresh()->isExplicitlyUnlocked());
    }

    public function test_future_quarter_is_not_auto_locked_even_if_before_manual_active(): void
    {
        $quarters = $this->createAllQuarters();

        $quarters[2]->update(['is_manually_set_active' => true]);

        $context = $this->service->contextForSchoolYear($this->schoolYear->id);
        $lockInfo = $context['quarterLocks'];

        $this->assertTrue($lockInfo[1]['is_locked'], 'Q1 has ended should be auto-locked');
        $this->assertFalse($lockInfo[2]['is_locked'], 'Q2 has not ended should not be auto-locked');
        $this->assertFalse($lockInfo[3]['is_locked'], 'Q3 is the current active should not be locked');
        $this->assertFalse($lockInfo[4]['is_locked'], 'Q4 is future should not be locked');
    }

    public function test_context_for_school_year_cache_is_cleared(): void
    {
        $quarters = $this->createAllQuarters();
        $quarters[1]->update(['is_manually_set_active' => true]);

        $context1 = $this->service->contextForSchoolYear($this->schoolYear->id);
        $this->assertFalse($context1['quarterLocks'][2]['is_locked']);

        $quarters[1]->update(['is_locked' => true]);

        QuarterLockService::clearCache($this->schoolYear->id);

        $context2 = $this->service->contextForSchoolYear($this->schoolYear->id);
        $this->assertTrue($context2['quarterLocks'][2]['is_locked']);
    }
}
