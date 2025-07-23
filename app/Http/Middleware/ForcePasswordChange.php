<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->temporary_password && !$request->routeIs('password.change') && !$request->routeIs('password.update')) {
            return redirect()->route('password.change')->with(
                'error',
                'Please change your password before you proceed.'
            );
        }

        return $next($request);
    }
}
