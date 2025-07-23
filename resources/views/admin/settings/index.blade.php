@extends('base')

@section('content')
    <div class="container-fluid">
        <h1>Settings</h1>
        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="teacher_enrollment" class="form-label">Enable Teacher Enrollment</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="teacher_enrollment" id="teacher_enrollment"
                        {{ $teacher_enrollment && $teacher_enrollment->value ? 'checked' : '' }}>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Save Settings</button>
        </form>
    </div>
@endsection
