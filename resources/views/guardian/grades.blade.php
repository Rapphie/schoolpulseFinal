@extends('base')

@section('title', 'Guardian Grades')

@section('content')
    <div class="container-fluid py-4">
        @livewire('guardian.student-grades')
    </div>
@endsection
