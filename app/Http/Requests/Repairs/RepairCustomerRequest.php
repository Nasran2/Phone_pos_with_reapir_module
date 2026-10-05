<?php

namespace App\Http\Requests\Repairs;

use Illuminate\Foundation\Http\FormRequest;

class RepairCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('repair.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['phone' => preg_replace('/[^0-9]/', '', (string) $this->input('phone'))]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:150'], 'phone' => ['required', 'string', 'regex:/^[0-9]{7,15}$/D'], 'email' => ['nullable', 'email', 'max:150'], 'address' => ['nullable', 'string', 'max:500']];
    }
}
