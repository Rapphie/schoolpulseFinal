<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    public function index()
    {
        $teacher_enrollment = Setting::where('key', 'teacher_enrollment')->first();
        $school_year = Setting::where('key', 'school_year')->first();
        return view('admin.settings.index', compact('teacher_enrollment', 'school_year'));
    }

    public function update(Request $request)
    {
        Setting::updateOrCreate(
            ['key' => 'teacher_enrollment'],
            ['value' => $request->has('teacher_enrollment') ? '1' : '0']
        );

        Setting::updateOrCreate(
            ['key' => 'school_year'],
            ['value' => $request->input('school_year')]
        );

        // Clear the sidebar settings cache so changes take effect immediately
        Cache::forget('sidebar_settings');

        return back()->with('success', 'Settings updated successfully.');
    }
}
