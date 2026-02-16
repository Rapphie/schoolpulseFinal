@extends('base')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">Schedule Details</h1>
            <a href="{{ route('admin.schedules.index') }}"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Back to Schedules</a>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @php
                    $sectionLabel = trim(implode(' - ', array_filter([
                        $schedule->class?->section?->gradeLevel?->name,
                        $schedule->class?->section?->name,
                    ])));
                    $days = is_array($schedule->day_of_week) ? implode(', ', array_map('ucfirst', $schedule->day_of_week)) : 'N/A';
                @endphp
                <p><strong>Section:</strong> {{ $sectionLabel !== '' ? $sectionLabel : 'N/A' }}</p>
                <p><strong>Subject:</strong> {{ $schedule->subject?->name ?? 'N/A' }}</p>
                <p><strong>Teacher:</strong>
                    {{ trim(($schedule->teacher?->user?->first_name ?? '') . ' ' . ($schedule->teacher?->user?->last_name ?? '')) ?: 'N/A' }}
                </p>
                <p><strong>Day of Week:</strong> {{ $days }}</p>
                <p><strong>Start Time:</strong>
                    {{ $schedule->start_time ? $schedule->start_time->format('h:i A') : 'N/A' }}</p>
                <p><strong>End Time:</strong> {{ $schedule->end_time ? $schedule->end_time->format('h:i A') : 'N/A' }}</p>
                <p><strong>Room:</strong> {{ $schedule->room ?: 'N/A' }}</p>
                <p><strong>School Year:</strong> {{ $schedule->class?->schoolYear?->name ?? 'N/A' }}</p>
                <p><strong>Quarter:</strong> N/A</p>
            </div>
        </div>
    </div>
@endsection
