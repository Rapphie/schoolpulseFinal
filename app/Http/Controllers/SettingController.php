<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function getSetting(Request $request)
    {
        $setting = Setting::where('key', $request->key)->first();

        return $setting ? $setting->value : null;
    }

    public function setSetting(Request $request)
    {
        $setting = Setting::updateOrCreate(
            ['key' => $request->key],
            ['value' => $request->value]
        );

        return response()->json($setting);
    }
}
