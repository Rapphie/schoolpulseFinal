<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.sidebar', function ($view) {
            $teacher_enrollment = Setting::where('key', 'teacher_enrollment')->first();
            $view->with('teacher_enrollment_enabled', $teacher_enrollment ? $teacher_enrollment->value : false);
        });
    }
}
