<p>Hello {{ $user->name ?? $user->email }},</p>
<p>You have requested to recover your account. Here is your temporary password:</p>
<p style="font-size: 1.5em; font-weight: bold;">{{ $temporaryPassword }}</p>
<p>This password is valid until <strong>{{ $expiresAt->format('F j, Y, g:i a') }}</strong> (30 minutes from request).
</p>
<p>Please use this password to log in. You can change your password after logging in.</p>
<p>If you did not request this, please ignore this email.</p>
<p>Thank you,<br>SchoolPulse Team</p>
