<?php

namespace App\Http\Requests\Repairs;

use App\Support\RepairSettings;
use Illuminate\Foundation\Http\FormRequest;

class RepairSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('repair.settings') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [];
        foreach (RepairSettings::definitions() as $key => $setting) {
            $rules[$key] = match ($setting[1]) {
                'checkbox' => ['nullable', 'boolean'],'number' => ['required', 'integer', 'min:0', 'max:10000'],'store' => ['nullable', 'exists:stores,id'],default => ['nullable', 'string', 'max:5000']
            };
        }
        $rules['job_prefix'] = ['required', 'regex:/^[A-Za-z0-9]{1,20}$/D'];
        $rules['image_limit'] = ['required', 'integer', 'between:1,50'];
        $rules['image_size_kb'] = ['required', 'integer', 'between:100,10240'];

        return $rules;
    }
}
