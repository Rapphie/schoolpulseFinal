<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;

class PopulateStudentMLFields extends Command
{
    protected $signature = 'students:populate-ml-fields {--force : Skip confirmation prompt}';

    protected $description = 'Populate distance_km, transportation, and family_income fields with random test data for students missing this data';

    protected array $transportationOptions = [
        'Walk',
        'Bicycle',
        'Motorcycle',
        'Tricycle',
        'Jeepney',
        'Bus',
        'Private Vehicle',
    ];

    protected array $familyIncomeOptions = [
        'Low',
        'Medium',
        'High',
    ];

    public function handle(): int
    {
        $count = Student::where(function ($query) {
            $query->whereNull('distance_km')
                ->orWhereNull('transportation')
                ->orWhereNull('family_income');
        })->count();

        if ($count === 0) {
            $this->info('All students already have ML feature fields populated. Nothing to do!');

            return Command::SUCCESS;
        }

        $this->info("Found {$count} students with missing ML feature fields.");

        if (! $this->option('force') && ! $this->confirm('Do you want to populate these fields with random test data?')) {
            $this->info('Operation cancelled.');

            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $updated = 0;

        Student::where(function ($query) {
            $query->whereNull('distance_km')
                ->orWhereNull('transportation')
                ->orWhereNull('family_income');
        })->chunk(100, function ($students) use (&$updated, $bar) {
            foreach ($students as $student) {
                $updates = [];

                if ($student->distance_km === null) {
                    $updates['distance_km'] = round(mt_rand(5, 150) / 10, 2);
                }

                if ($student->transportation === null) {
                    $updates['transportation'] = $this->transportationOptions[array_rand($this->transportationOptions)];
                }

                if ($student->family_income === null) {
                    $rand = mt_rand(1, 100);
                    if ($rand <= 40) {
                        $updates['family_income'] = 'Low';
                    } elseif ($rand <= 80) {
                        $updates['family_income'] = 'Medium';
                    } else {
                        $updates['family_income'] = 'High';
                    }
                }

                if (! empty($updates)) {
                    $student->update($updates);
                    $updated++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("Successfully populated ML feature fields for {$updated} students.");

        $this->newLine();
        $this->info('Sample of updated student data:');

        $samples = Student::whereNotNull('distance_km')
            ->whereNotNull('transportation')
            ->whereNotNull('family_income')
            ->take(5)
            ->get(['id', 'first_name', 'last_name', 'distance_km', 'transportation', 'family_income']);

        $this->table(
            ['ID', 'Name', 'Distance (km)', 'Transportation', 'Socioeconomic Status'],
            $samples->map(fn ($s) => [
                $s->id,
                "{$s->first_name} {$s->last_name}",
                $s->distance_km,
                $s->transportation,
                $s->family_income,
            ])
        );

        return Command::SUCCESS;
    }
}
