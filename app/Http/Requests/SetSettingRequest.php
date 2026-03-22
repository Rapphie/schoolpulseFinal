<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'key' => 'required|string|max:255',
            'value' => 'nullable|string',
        ];
    }
}
