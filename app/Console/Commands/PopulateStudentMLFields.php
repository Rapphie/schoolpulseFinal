<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;

class PopulateStudentMLFields extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:populate-ml-fields {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate distance_km, transportation, and family_income fields with random test data for students missing this data';

    /**
     * Transportation options matching the model's expected values.
     */
    protected array $transportationOptions = [
        'Walk',
        'Bicycle',
        'Motorcycle',
        'Tricycle',
        'Jeepney',
        'Bus',
        'Private Vehicle',
    ];

    /**
     * Socioeconomic status options matching the model's expected values.
     */
    protected array $familyIncomeOptions = [
        'Low',
        'Medium',
        'High',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $studentsToUpdate = Student::where(function ($query) {
            $query->whereNull('distance_km')
                ->orWhereNull('transportation')
                ->orWhereNull('family_income');
        })->get();

        $count = $studentsToUpdate->count();

        if ($count === 0) {
            $this->info('All students already have ML feature fields populated. Nothing to do!');
            return Command::SUCCESS;
        }

        $this->info("Found {$count} students with missing ML feature fields.");

        if (!$this->option('force') && !$this->confirm('Do you want to populate these fields with random test data?')) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $updated = 0;

        foreach ($studentsToUpdate as $student) {
            $updates = [];

            if ($student->distance_km === null) {
                // Random distance between 0.5 and 15 km, with 2 decimal places
                $updates['distance_km'] = round(mt_rand(5, 150) / 10, 2);
            }

            if ($student->transportation === null) {
                $updates['transportation'] = $this->transportationOptions[array_rand($this->transportationOptions)];
            }

            if ($student->family_income === null) {
                // Weighted random: 40% Low, 40% Medium, 20% High
                $rand = mt_rand(1, 100);
                if ($rand <= 40) {
                    $updates['family_income'] = 'Low';
                } elseif ($rand <= 80) {
                    $updates['family_income'] = 'Medium';
                } else {
                    $updates['family_income'] = 'High';
                }
            }

            if (!empty($updates)) {
                $student->update($updates);
                $updated++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Successfully populated ML feature fields for {$updated} students.");

        // Show a sample of the updated data
        $this->newLine();
        $this->info('Sample of updated student data:');

        $samples = Student::whereNotNull('distance_km')
            ->whereNotNull('transportation')
            ->whereNotNull('family_income')
            ->take(5)
            ->get(['id', 'first_name', 'last_name', 'distance_km', 'transportation', 'family_income']);

        $this->table(
            ['ID', 'Name', 'Distance (km)', 'Transportation', 'Socioeconomic Status'],
            $samples->map(fn($s) => [
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
