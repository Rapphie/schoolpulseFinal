<?php

namespace App\Console\Commands;

use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Services\StudentFeaturesService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateFeatureSnapshots extends Command
{
    protected $signature = 'features:generate {--limit=50} {--school_year_id=}';

    protected $description = 'Compute and persist student feature snapshots for active school year.';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        $syId = $this->option('school_year_id');
        $sy = null;
        if ($syId) {
            $sy = SchoolYear::find($syId);
            if (! $sy) {
                $this->error('SchoolYear not found for id: '.$syId);

                return 1;
            }
        } else {
            $sy = SchoolYear::where('is_active', true)->first();
            if (! $sy) {
                $this->error('No active school year found.');

                return 1;
            }
        }

        $studentIds = Enrollment::where('school_year_id', $sy->id)->pluck('student_id')->unique()->take($limit)->values()->toArray();
        if (empty($studentIds)) {
            $this->info('No students found to process.');

            return 0;
        }

        $svc = new StudentFeaturesService;
        $features = $svc->computeBatchFeaturesForStudents($studentIds, $sy->id, Carbon::now());
        $svc->persistSnapshots($features, $sy->id, env('FEATURE_MODEL_VERSION', null));

        $this->info('Persisted snapshots for '.count($features).' students.');

        return 0;
    }
}
