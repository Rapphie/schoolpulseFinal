<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function authenticate(Request $request)
    {
        $credentials = [
            'email' => $request['email'],
            'password' => $request['password'],
            'role_id' => $request['role']
        ];

        $remember = $request->has('remember');

        // Normal login
        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            return redirect()->intended('/');
        }

        // Check for temporary password
        $user = \App\Models\User::where('email', $request['email'])->first();
        if ($user && $user->temporary_password && $user->temporary_password_expires_at && now()->lessThanOrEqualTo($user->temporary_password_expires_at)) {
            if ($request['password'] === $user->temporary_password && $user->role_id == $request['role']) {
                Auth::login($user, $remember);
                $request->session()->regenerate();
                return redirect()->route('password.change');
            }
        }

        return back()->withErrors([
            'error' => 'The provided credentials do not match our records.',
        ])->onlyInput('error');
    }
    public function login()
    {
        return view('auth.login');
    }



    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
