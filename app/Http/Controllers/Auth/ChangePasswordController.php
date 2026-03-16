<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ChangePasswordController extends Controller
{
    /**
     * Show the form for the user to change their password.
     *
     * @return \Illuminate\View\View
     */
    public function showChangePasswordForm()
    {
        return view('auth.change-password');
    }

    /**
     * Update the user's password.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', function ($attribute, $value, $fail) {
                $user = Auth::user();
                if ($user->temporary_password) {
                    // If it is a temporary password flow, check against the temporary password
                    if ($value !== $user->temporary_password) {
                        $fail('The provided temporary password does not match.');
                    }
                } else {
                    // If it's not a temporary password flow, check the actual password
                    if (! Hash::check($value, $user->password)) {
                        $fail('The provided password does not match your current password.');
                    }
                }
            }],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        Auth::user()->forceFill([
            'password' => Hash::make($request->password),
            'temporary_password' => null,
            'temporary_password_expires_at' => null,
        ])->save();
        session()->forget('error');

        return redirect('/')->with('success', 'Your password has been changed successfully.');
    }
}
