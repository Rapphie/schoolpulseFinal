@extends('base')

@section('title', 'Guardian Attendance')

@section('content')
    <div class="container-fluid py-4">
        @livewire('guardian.student-attendance')
    </div>
@endsection
