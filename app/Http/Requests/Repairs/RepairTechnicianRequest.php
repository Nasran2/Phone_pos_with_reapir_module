<?php

namespace App\Http\Requests\Repairs;

use Illuminate\Foundation\Http\FormRequest;

class RepairTechnicianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('repair.technicians') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:150'], 'phone' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email', 'max:150'], 'specialization' => ['nullable', 'string', 'max:255'], 'status' => ['required', 'in:Active,Inactive,On Leave'], 'commission_rate' => ['required', 'decimal:0,2', 'between:0,100'], 'notes' => ['nullable', 'string', 'max:2000'], 'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']];
    }
}
