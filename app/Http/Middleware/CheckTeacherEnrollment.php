<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CheckTeacherEnrollment
{
    /**
     * Handle an incoming request.
     *
     * Check if teacher enrollment is enabled before allowing access to enrollment routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the enrollment setting (using cache for performance)
        $settings = Cache::remember('sidebar_settings', 300, function () {
            return Setting::whereIn('key', ['teacher_enrollment', 'school_year'])
                ->pluck('value', 'key');
        });

        $enrollmentEnabled = isset($settings['teacher_enrollment'])
            && filter_var($settings['teacher_enrollment'], FILTER_VALIDATE_BOOLEAN);

        if (!$enrollmentEnabled) {
            // If it's an AJAX request, return JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Enrollment is currently disabled by the administrator.'
                ], 403);
            }

            // Otherwise redirect to the disabled page or show a flash message
            return redirect()->route('teacher.dashboard')
                ->with('error', 'Enrollment is currently disabled by the administrator.');
        }

        return $next($request);
    }
}
