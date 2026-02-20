<?php

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'password.force-change' => \App\Http\Middleware\ForcePasswordChange::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'enrollment.enabled' => \App\Http\Middleware\CheckTeacherEnrollment::class,
        ]);

        $middleware->web(remove: [
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $middleware->web(append: [
            VerifyCsrfToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $handleExpiredSession = function (Request $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Your session has expired. Please retry your request.',
                ], 419);
            }

            $nonFlashInputs = [
                '_token',
                'password',
                'password_confirmation',
                'current_password',
                'new_password',
                'new_password_confirmation',
            ];

            return redirect()
                ->back()
                ->withInput($request->except($nonFlashInputs))
                ->with('warning', 'Your session expired. Please try again.');
        };

        $exceptions->render(function (TokenMismatchException $_, Request $request) use ($handleExpiredSession) {
            return $handleExpiredSession($request);
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) use ($handleExpiredSession) {
            if ($exception->getStatusCode() !== 419) {
                return null;
            }

            return $handleExpiredSession($request);
        });
    })->create();
