<!DOCTYPE html>
<html>
<head>
    <title>Absent Alert</title>
</head>
<body>
    <p>Dear {{ $teacher->name }},</p>

    <p>This is to inform you that {{ $student->name }} has been marked absent for {{ $absenceCount }} consecutive days.</p>

    <p>Thank you,</p>
    <p>SchoolPulse</p>
</body>
</html>
