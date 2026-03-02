<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Welcome Email (Updated) in SchoolPulse</title>
</head>

<body>
    <h4>Welcome, this is SchoolPulse. {{ $user->first_name }}!</h4>
    <p>Your account email has been updated successfully.</p>
    <p>You can now log in to the system using your new credentials:</p>
    <ul>
        <li><strong>Email:</strong> {{ $user->email }}</li>
        <li><strong>Password:</strong> {{ $password }}</li>
        <li><strong>login as:</strong> {{ $user->role->name }}</li>
    </ul>
    <p>For security reasons, we strongly recommend that you change your password after your first login.</p>
    <a href="https://schoolpulse.online">Login here</a>
    <p>Thank you!</p>
</body>

</html>
