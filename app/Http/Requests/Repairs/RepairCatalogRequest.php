<?php

namespace App\Http\Requests\Repairs;

use Illuminate\Foundation\Http\FormRequest;

class RepairCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('repair.settings') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $fields = ['is_active', 'is_default', 'is_completed', 'is_delivered', 'is_cancelled', 'is_ready', 'is_waiting_approval', 'is_waiting_parts'];
        foreach ($fields as $field) {
            $this->merge([$field => $this->boolean($field)]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = ['name' => ['required', 'string', 'max:100'], 'sort_order' => ['required', 'integer', 'min:0', 'max:10000'], 'is_active' => ['boolean']];
        if ($this->route('catalog') === 'statuses') {
            $rules['color'] = ['required', 'regex:/^#[0-9a-fA-F]{6}$/D'];
            foreach (['is_default', 'is_completed', 'is_delivered', 'is_cancelled', 'is_ready', 'is_waiting_approval', 'is_waiting_parts'] as $key) {
                $rules[$key] = ['boolean'];
            }
        }
        if ($this->route('catalog') === 'charges') {
            $rules['default_amount'] = ['required', 'decimal:0,2', 'min:0', 'max:999999999'];
        }

        return $rules;
    }
}
