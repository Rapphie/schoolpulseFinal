<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Only register Telescope when explicitly enabled
        if ($this->app->environment('local') && config('telescope.enabled')) {
            $this->app->register(\App\Providers\TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // existing Sidebar Logic (Keep this!)
        View::composer('components.sidebar', function ($view) {
            // Cache settings for 5 minutes to reduce database queries on every page load
            $settings = Cache::remember('sidebar_settings', 300, function () {
                return Setting::whereIn('key', ['teacher_enrollment', 'school_year'])
                    ->pluck('value', 'key');
            });

            // Cast to boolean - handles string '1'/'0', boolean true/false, and integer 1/0
            $enrollmentEnabled = isset($settings['teacher_enrollment'])
                && filter_var($settings['teacher_enrollment'], FILTER_VALIDATE_BOOLEAN);

            $view->with('teacher_enrollment_enabled', $enrollmentEnabled);
            $view->with('school_year', $settings['school_year'] ?? null);
        });
    }
}
