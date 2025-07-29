@component('mail::message')
# Student Absent Alert

Dear {{ $teacher->user->name }},

This is to inform you that **{{ $student->full_name }}** has been marked as absent for **{{ $consecutiveAbsences }}** consecutive days.

Thank you,

{{ config('app.name') }}
@endcomponent
