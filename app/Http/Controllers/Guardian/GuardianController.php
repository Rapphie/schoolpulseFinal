<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class GuardianController extends Controller
{
    public function dashboard(): View
    {
        return view('guardian.dashboard');
    }

    public function grades(): View
    {
        return view('guardian.grades');
    }

    public function attendance(): View
    {
        return view('guardian.attendance');
    }
}
