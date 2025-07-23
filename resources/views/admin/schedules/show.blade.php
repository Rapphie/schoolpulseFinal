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
                <p><strong>Section:</strong> {{ $schedule->section->name }}</p>
                <p><strong>Subject:</strong> {{ $schedule->subject->name }}</p>
                <p><strong>Teacher:</strong> {{ $schedule->teacher->user->first_name }}
                    {{ $schedule->teacher->user->last_name }}</p>
                <p><strong>Day of Week:</strong> {{ $schedule->day_of_week }}</p>
                <p><strong>Start Time:</strong> {{ date('h:i A', strtotime($schedule->start_time)) }}</p>
                <p><strong>End Time:</strong> {{ date('h:i A', strtotime($schedule->end_time)) }}</p>
                <p><strong>Room:</strong> {{ $schedule->room }}</p>
                <p><strong>School Year:</strong> {{ $schedule->school_year }}</p>
                <p><strong>Quarter:</strong> {{ $schedule->quarter }}</p>
            </div>
        </div>
    </div>
@endsection
