<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\ResetUserPasswordRequest;
use App\Mail\TemporaryPasswordMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function resetUserPassword(ResetUserPasswordRequest $request, User $user): RedirectResponse
    {
        $tempPassword = Str::random(6);
        $expiresAt = now('Asia\Singapore');
        $user->temporary_password = $tempPassword;
        $user->temporary_password_expires_at = $expiresAt;
        $user->save();
        Mail::to($user->email)->queue(new TemporaryPasswordMail($user, $tempPassword, $expiresAt));

        return back()->with('success', 'Temporary password sent to user email.');
    }
}
