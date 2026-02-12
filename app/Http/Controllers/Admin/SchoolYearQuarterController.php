<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use App\Models\SchoolYearQuarter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SchoolYearQuarterController extends Controller
{
    /**
     * Display quarters for a school year.
     */
    public function index(SchoolYear $schoolYear)
    {
        $quarters = $schoolYear->quarters()->get();

        return view('admin.school-years.quarters.index', [
            'schoolYear' => $schoolYear,
            'quarters' => $quarters,
            'quarterNames' => SchoolYearQuarter::QUARTER_NAMES,
        ]);
    }

    /**
     * Store a new quarter for a school year.
     */
    public function store(Request $request, SchoolYear $schoolYear)
    {
        $validated = $request->validate([
            'quarter' => [
                'required',
                'integer',
                Rule::in([1, 2, 3, 4]),
                Rule::unique('school_year_quarters')->where(function ($query) use ($schoolYear) {
                    return $query->where('school_year_id', $schoolYear->id);
                }),
            ],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'grade_submission_deadline' => 'nullable|date|after_or_equal:end_date',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        // Validate dates are within school year
        if ($startDate->lt($schoolYear->start_date) || $endDate->gt($schoolYear->end_date)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Quarter dates must be within the school year period ('.
                    Carbon::parse($schoolYear->start_date)->format('M d, Y').' - '.
                    Carbon::parse($schoolYear->end_date)->format('M d, Y').').');
        }

        // Check for overlapping quarters
        $overlapping = SchoolYearQuarter::findOverlapping($schoolYear->id, $startDate, $endDate);
        if ($overlapping) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Date range overlaps with {$overlapping->name}.");
        }

        $quarter = $schoolYear->quarters()->create([
            'quarter' => $validated['quarter'],
            'name' => SchoolYearQuarter::QUARTER_NAMES[$validated['quarter']],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'grade_submission_deadline' => $validated['grade_submission_deadline'] ?? null,
            'is_locked' => false,
        ]);

        return redirect()->back()->with('success', "{$quarter->name} added successfully.");
    }

    /**
     * Update a quarter.
     */
    public function update(Request $request, SchoolYear $schoolYear, SchoolYearQuarter $quarter)
    {
        // Ensure the quarter belongs to the school year
        if ($quarter->school_year_id !== $schoolYear->id) {
            abort(404);
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'grade_submission_deadline' => 'nullable|date|after_or_equal:end_date',
            'is_locked' => 'boolean',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        // Validate dates are within school year
        if ($startDate->lt($schoolYear->start_date) || $endDate->gt($schoolYear->end_date)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Quarter dates must be within the school year period.');
        }

        // Check for overlapping quarters (excluding current)
        $overlapping = SchoolYearQuarter::findOverlapping($schoolYear->id, $startDate, $endDate, $quarter->id);
        if ($overlapping) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Date range overlaps with {$overlapping->name}.");
        }

        $quarter->update([
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'grade_submission_deadline' => $validated['grade_submission_deadline'] ?? null,
            'is_locked' => $request->has('is_locked'),
        ]);

        return redirect()->back()->with('success', "{$quarter->name} updated successfully.");
    }

    /**
     * Delete a quarter.
     */
    public function destroy(SchoolYear $schoolYear, SchoolYearQuarter $quarter)
    {
        if ($quarter->school_year_id !== $schoolYear->id) {
            abort(404);
        }

        $quarterName = $quarter->name;
        $quarter->delete();

        return redirect()->back()->with('success', "{$quarterName} deleted successfully.");
    }

    /**
     * Toggle lock status of a quarter.
     */
    public function toggleLock(SchoolYear $schoolYear, SchoolYearQuarter $quarter)
    {
        if ($quarter->school_year_id !== $schoolYear->id) {
            abort(404);
        }

        $quarter->update(['is_locked' => ! $quarter->is_locked]);

        $status = $quarter->is_locked ? 'locked' : 'unlocked';

        return redirect()->back()->with('success', "{$quarter->name} has been {$status}.");
    }

    /**
     * Auto-generate all 4 quarters by dividing school year evenly.
     */
    public function autoGenerate(SchoolYear $schoolYear)
    {
        // Check if quarters already exist
        if ($schoolYear->quarters()->count() > 0) {
            return redirect()->back()->with('error', 'Quarters already exist for this school year. Delete them first to regenerate.');
        }

        $start = Carbon::parse($schoolYear->start_date);
        $end = Carbon::parse($schoolYear->end_date);
        $totalDays = $start->diffInDays($end);
        $quarterDays = (int) floor($totalDays / 4);

        DB::beginTransaction();
        try {
            for ($q = 1; $q <= 4; $q++) {
                $qStart = $start->copy()->addDays(($q - 1) * $quarterDays);
                $qEnd = ($q === 4)
                    ? $end->copy()
                    : $start->copy()->addDays($q * $quarterDays)->subDay();

                $schoolYear->quarters()->create([
                    'quarter' => $q,
                    'name' => SchoolYearQuarter::QUARTER_NAMES[$q],
                    'start_date' => $qStart,
                    'end_date' => $qEnd,
                    'grade_submission_deadline' => $qEnd->copy()->addDays(7), // 7 days after quarter ends
                    'is_locked' => false,
                ]);
            }
            DB::commit();

            return redirect()->back()->with('success', 'All 4 quarters have been auto-generated. You can adjust the dates as needed.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to generate quarters: '.$e->getMessage());
        }
    }

    /**
     * Set the active period for a school year.
     */
    public function setActive(SchoolYear $schoolYear, SchoolYearQuarter $quarter)
    {
        if ($quarter->school_year_id !== $schoolYear->id) {
            abort(404);
        }

        DB::transaction(function () use ($quarter, $schoolYear) {
            // Deactivate all other school years and quarters
            SchoolYear::where('is_active', true)->update(['is_active' => false]);
            SchoolYearQuarter::where('is_manually_set_active', true)->update(['is_manually_set_active' => false]);

            // Activate the selected school year and quarter
            $schoolYear->update(['is_active' => true]);
            $quarter->update(['is_manually_set_active' => true]);
        });

        SchoolYear::clearAdminViewSchoolYear();

        return redirect()->back()->with('success', "{$quarter->name} of {$schoolYear->name} is now the active period.");
    }

    /**
     * Unset the manually active quarter (revert to date-based detection).
     */
    public function unsetActive(SchoolYear $schoolYear, SchoolYearQuarter $quarter): \Illuminate\Http\RedirectResponse
    {
        if ($quarter->school_year_id !== $schoolYear->id) {
            abort(404);
        }

        if (! $quarter->is_manually_set_active) {
            return redirect()->back()->with('error', "{$quarter->name} is not manually set as active.");
        }

        $quarter->update(['is_manually_set_active' => false]);

        return redirect()->back()->with('success', "{$quarter->name} manual override removed. The system will now use date-based quarter detection.");
    }
}
