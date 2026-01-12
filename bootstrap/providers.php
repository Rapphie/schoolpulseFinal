<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\RouteServiceProvider::class,
    // TelescopeServiceProvider loaded conditionally - see AppServiceProvider
    // Maatwebsite\Excel is auto-discovered via package discovery
];
