<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function authenticate(Request $request)
    {
        $credentials = [
            'email' => trim($request['email']),
            'password' => trim($request['password']),
            'role_id' => $request['role'],
        ];

        $remember = $request->has('remember');

        // Normal login
        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->intended('/');
        }

        // Check for temporary password
        $user = User::firstWhere('email', $request->input('email'));
        if ($user?->temporary_password && $user->temporary_password_expires_at && now()->lessThanOrEqualTo($user->temporary_password_expires_at)) {
            if ($request['password'] === $user->temporary_password && $user->role_id == $request['role']) {
                Auth::login($user, $remember);
                $request->session()->regenerate();

                return redirect()->route('password.change');
            }
        }

        return back()->withErrors([
            'error' => 'The provided credentials do not match our records.',
        ])->withInput($request->except('password'));
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
