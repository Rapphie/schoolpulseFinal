<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Admin: Reset a user's password and email a temporary password
     */
    public function resetUserPassword(Request $request, User $user)
    {
        $tempPassword = \Illuminate\Support\Str::random(6);
        $expiresAt = now('Asia\Singapore');
        $user->temporary_password = $tempPassword;
        $user->temporary_password_expires_at = $expiresAt;
        $user->save();
        \Illuminate\Support\Facades\Mail::to($user->email)->queue(new \App\Mail\TemporaryPasswordMail($user, $tempPassword, $expiresAt));

        return back()->with('success', 'Temporary password sent to user email.');
    }
}
