<?php

namespace App\Http\Requests\Repairs;

use App\Services\MixedPaymentService;
use App\Support\RepairSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RepairIntakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission($this->isMethod('POST') ? 'repair.create' : 'repair.edit') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['urgent' => $this->boolean('urgent'), 'is_warranty' => $this->boolean('is_warranty'), 'clear_credential' => $this->boolean('clear_credential'), 'discount_type' => $this->input('discount_type', 'fixed'), 'discount_value' => $this->input('discount_value', 0)]);
        $device = $this->input('device', []);
        $device['google_lock'] = $this->boolean('device.google_lock');
        $device['apple_id_lock'] = $this->boolean('device.apple_id_lock');
        $device['accessories_received'] = $device['accessories_received'] ?? [];
        if ($this->filled('accessory_other')) {
            $device['accessories_received'][] = $this->input('accessory_other');
        }
        foreach (['Water damage', 'Missing screws', 'Missing parts', 'Previous repair'] as $condition) {
            $device['physical_condition'][$condition] = $device['physical_condition'][$condition] ?? 'No';
        }
        $this->merge(['device' => $device]);
        if ($this->input('device.unlock_type') === 'Pattern') {
            $d = $this->input('device', []);
            $d['unlock_secret'] = preg_replace('/[ ,]+/', '-', trim((string) ($d['unlock_secret'] ?? '')));
            $this->merge(['device' => $d]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = ['customer_id' => ['required', Rule::exists('customers', 'id')->whereNull('deleted_at')], 'store_id' => ['required', 'exists:stores,id'], 'estimated_cost' => ['nullable', 'decimal:0,2', 'min:0', 'max:99999999999'], 'urgent' => ['boolean'], 'is_warranty' => ['boolean'], 'clear_credential' => ['boolean'], 'expected_completion_date' => ['nullable', 'date'], 'problem_description' => ['nullable', 'string', 'max:5000'], 'customer_request' => ['nullable', 'string', 'max:5000'], 'problem_ids' => ['nullable', 'array'], 'problem_ids.*' => ['exists:repair_problems,id'], 'device' => ['required', 'array'], 'device.unlock_type' => ['required', 'in:None,PIN,Password,Pattern'], 'device.unlock_secret' => ['nullable', 'string', 'max:256'], 'device.battery_percent' => ['nullable', 'integer', 'between:0,100'], 'device.google_lock' => ['nullable', 'boolean'], 'device.apple_id_lock' => ['nullable', 'boolean'], 'device.accessories_received' => ['nullable', 'array', 'max:30'], 'device.accessories_received.*' => ['string', 'max:100'], 'device.physical_condition' => ['nullable', 'array', 'max:20'], 'device.physical_condition.*' => ['nullable', 'string', 'max:500'], 'checklist' => ['nullable', 'array', 'max:40'], 'checklist.*' => ['in:Working,Not working,Not tested'], 'custom_tests' => ['nullable', 'string', 'max:2000'], 'discount_type' => ['in:fixed,percentage'], 'discount_value' => ['decimal:0,2', 'min:0', $this->input('discount_type') === 'percentage' ? 'max:100' : 'max:99999999999']];
        foreach (['device_type', 'brand', 'model', 'imei_1', 'imei_2', 'serial_number', 'color', 'storage', 'ram', 'carrier'] as $key) {
            $rules['device.'.$key] = [$key === 'model' ? 'required' : 'nullable', 'string', 'max:150'];
        }
        if ($this->isMethod('POST')) {
            $rules['intake_key'] = ['required', 'uuid'];
            $rules['advance_amount'] = ['nullable', 'decimal:0,2', 'min:0', 'max:999999999'];
            $rules['advance_method'] = ['nullable', Rule::in(array_diff(MixedPaymentService::paymentMethods(), ['Cheque']))];
            $rules['images'] = ['nullable', 'array', 'max:'.(int) RepairSettings::get('image_limit', 12)];
            $rules['images.*'] = ['image', 'mimes:jpg,jpeg,png,webp', 'max:'.(int) RepairSettings::get('image_size_kb', 4096)];
        }
        $rules['checklist'][] = function ($attribute, $value, $fail) {
            foreach (array_keys($value) as $name) {
                if (! is_string($name) || strlen($name) > 100 || preg_match('/[.\[\]<>]/', $name)) {
                    $fail('Function test names must be up to 100 characters without dots or brackets.');
                }
            }
        };
        if ($this->filled('device.unlock_secret')) {
            if ($this->input('device.unlock_type') === 'PIN') {
                $rules['device.unlock_secret'][] = 'regex:/^\d{4,16}$/D';
            }
            if ($this->input('device.unlock_type') === 'Pattern') {
                $rules['device.unlock_secret'][] = function ($attribute, $value, $fail) {
                    $dots = explode('-', $value);
                    if (count($dots) < 4 || count($dots) > 9 || count(array_unique($dots)) !== count($dots) || preg_match('/^[1-9](?:-[1-9]){3,8}$/D', $value) !== 1) {
                        $fail('Use four to nine distinct pattern dots.');
                    }
                };
            }
        }
        if ($this->isMethod('POST') && $this->input('device.unlock_type') !== 'None') {
            $rules['device.unlock_secret'][0] = 'required';
        }

        return $rules;
    }
}
