<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Hash;

class ClearTemporaryPasswordOnLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;
        $password = request()->input('password'); // Or however you get the password from the request

        if ($user->temporary_password && Hash::check($password, $user->password)) {
            $user->temporary_password = null;
            $user->save();
        }
    }
}
