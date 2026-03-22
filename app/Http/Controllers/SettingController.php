<?php

namespace App\Http\Controllers;

use App\Http\Requests\SetSettingRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function getSetting(Request $request): mixed
    {
        $setting = Setting::where('key', $request->key)->first();

        return $setting ? $setting->value : null;
    }

    public function setSetting(SetSettingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $setting = Setting::updateOrCreate(
            ['key' => $validated['key']],
            ['value' => $validated['value'] ?? null]
        );

        return response()->json($setting);
    }
}
