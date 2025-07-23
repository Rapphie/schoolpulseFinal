<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Mail\TemporaryPasswordMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;


class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
        $user = User::where('email', $request->email)->first();
        $tempPassword = Str::random(6);
        $expiresAt = Carbon::now()->addMinutes(30);
        $user->temporary_password = $tempPassword;
        $user->temporary_password_expires_at = $expiresAt;
        $user->save();
        Mail::to($user->email)->send(new TemporaryPasswordMail($user, $tempPassword, $expiresAt));
        return back()->with('status', 'A temporary password has been sent to your email.');
    }
}
