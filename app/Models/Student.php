<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'section_id',
        'first_name',
        'last_name',
        'bar_code',
        'qr_code',
        'lrn',
        'birthdate',
        'gender',
        'address',
        'contact_number',
        'guardian_name',
        'guardian_contact',
        'status',
        'enrollment_date',
        'remarks',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'enrollment_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }


    public function getAttendanceRateAttribute()
    {
        $total = $this->attendances()->count();
        if ($total === 0) {
            return 0;
        }

        $present = $this->attendances()
            ->whereIn('status', ['present', 'late'])
            ->count();

        return round(($present / $total) * 100, 1);
    }

    public function getQuizAverageAttribute()
    {
        $quizzes = $this->grades()
            ->where('assessment_type', 'quiz')
            ->get();

        if ($quizzes->isEmpty()) {
            return null;
        }

        // Calculate total percentage using the accessor method
        $totalPercentage = 0;
        foreach ($quizzes as $quiz) {
            $totalPercentage += $quiz->score_percentage;
        }

        return round($totalPercentage / $quizzes->count(), 1);
    }

    public function getExamAverageAttribute()
    {
        $exams = $this->grades()
            ->where('assessment_type', 'exam')
            ->get();

        if ($exams->isEmpty()) {
            return null;
        }

        // Calculate total percentage using the accessor method
        $totalPercentage = 0;
        foreach ($exams as $exam) {
            $totalPercentage += $exam->score_percentage;
        }

        return round($totalPercentage / $exams->count(), 1);
    }

    public function getOverallAverageAttribute()
    {
        $grades = $this->grades()->get();

        if ($grades->isEmpty()) {
            return null;
        }

        // Calculate total percentage using the accessor method
        $totalPercentage = 0;
        foreach ($grades as $grade) {
            $totalPercentage += $grade->score_percentage;
        }

        return round($totalPercentage / $grades->count(), 1);
    }

    public function getPerformanceTrendAttribute()
    {
        // Get the last 5 grades ordered by assessment date
        $recentGrades = $this->grades()
            ->orderBy('assessment_date', 'desc')
            ->take(5)
            ->get()
            ->sortBy('assessment_date');

        if ($recentGrades->count() < 2) {
            return 'stable';
        }

        // First half of the recent grades
        $firstHalf = $recentGrades->take(ceil($recentGrades->count() / 2));
        $firstSum = 0;
        foreach ($firstHalf as $grade) {
            $firstSum += $grade->score_percentage;
        }
        $firstAvg = $firstSum / $firstHalf->count();

        // Second half of the recent grades
        $secondHalf = $recentGrades->skip(floor($recentGrades->count() / 2));
        $secondSum = 0;
        foreach ($secondHalf as $grade) {
            $secondSum += $grade->score_percentage;
        }
        $secondAvg = $secondSum / $secondHalf->count();

        $difference = $secondAvg - $firstAvg;

        if ($difference >= 5) {
            return 'improving';
        } elseif ($difference <= -5) {
            return 'declining';
        } else {
            return 'stable';
        }
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getGradeInSubject($subjectId, $gradingPeriod = null)
    {
        $query = $this->grades()->where('subject_id', $subjectId);

        if ($gradingPeriod) {
            $query->where('grading_period', $gradingPeriod);
        }

        return $query->first()?->grade;
    }

    public function getAttendanceSummary($startDate = null, $endDate = null)
    {
        $query = $this->attendances();

        if ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }

        return [
            'present' => (clone $query)->where('status', 'present')->count(),
            'absent' => (clone $query)->where('status', 'absent')->count(),
            'late' => (clone $query)->where('status', 'late')->count(),
            'excused' => (clone $query)->where('status', 'excused')->count(),
        ];
    }
}
