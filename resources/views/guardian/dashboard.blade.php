@extends('base')

@section('title', 'Guardian Dashboard')

@section('content')
    <div class="container-fluid py-4">
        @livewire('guardian.dashboard')
    </div>
@endsection
