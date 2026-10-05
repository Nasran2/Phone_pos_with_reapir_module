<?php

namespace App\Http\Requests\Repairs;

use App\Services\MixedPaymentService;
use App\Support\RepairSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RepairActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = match ($this->route('action')) {
            'status' => 'repair.status.change','assign' => 'repair.assign','part','part-state' => 'repair.parts','payment' => 'repair.payments',
            'note' => 'repair.edit','checklist','approval','warranty','charge','charge-delete','pricing','photo','photo-delete' => 'repair.edit',default => 'repair.view'
        };

        return $this->user()?->hasPermission($permission) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['return_parts' => $this->boolean('return_parts'), 'is_advance' => $this->boolean('is_advance')]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return match ($this->route('action')) {
            'status' => ['repair_status_id' => ['required', 'exists:repair_statuses,id'], 'note' => ['nullable', 'string', 'max:2000'], 'return_parts' => ['boolean']],
            'assign' => ['technician_id' => ['required', 'exists:repair_technicians,id']],
            'part' => ['product_id' => ['required', 'exists:products,id'], 'variant_id' => ['nullable', 'exists:product_variants,id'], 'quantity' => ['required', 'decimal:0,2', 'gt:0', 'max:999999'], 'selling_price' => ['nullable', 'decimal:0,2', 'min:0', 'max:999999999'], 'status' => ['required', 'in:Reserved,Issued'], 'request_key' => ['required', 'uuid']],
            'part-state' => ['part_id' => ['required', 'exists:repair_parts,id'], 'operation' => ['required', 'in:issue,return']],
            'charge' => ['name' => ['required', 'string', 'max:150'], 'amount' => ['required', 'decimal:0,2', 'min:0', 'max:999999999'], 'charge_id' => ['nullable', 'exists:repair_charge_items,id']],
            'charge-delete' => ['charge_id' => ['required', 'exists:repair_charge_items,id']],
            'pricing' => ['discount_type' => ['required', 'in:fixed,percentage'], 'discount_value' => ['required', 'decimal:0,2', 'min:0', $this->input('discount_type') === 'percentage' ? 'max:100' : 'max:999999999']],
            'payment' => ['payment_date' => ['required', 'date'], 'amount' => ['required', 'decimal:0,2', 'gt:0', 'max:999999999'], 'payment_method' => ['required', Rule::in(MixedPaymentService::paymentMethods())], 'reference_no' => ['nullable', 'string', 'max:150'], 'notes' => ['nullable', 'string', 'max:2000'], 'account_id' => ['nullable', Rule::exists('accounts', 'id')->where('account_type', 'Asset')->where('status', true)], 'is_advance' => ['boolean'], 'request_key' => ['required', 'uuid'], 'cheque' => [$this->input('payment_method') === 'Cheque' ? 'required' : 'nullable', 'array'], 'cheque.cheque_number' => ['required_if:payment_method,Cheque', 'nullable', 'string', 'max:100'], 'cheque.cheque_date' => ['required_if:payment_method,Cheque', 'nullable', 'date'], 'cheque.bank_name' => ['required_if:payment_method,Cheque', 'nullable', 'string', 'max:150'], 'cheque.account_holder' => ['required_if:payment_method,Cheque', 'nullable', 'string', 'max:150'], 'cheque.account_number' => ['nullable', 'string', 'max:100'], 'cheque.branch_name' => ['nullable', 'string', 'max:150']],
            'note' => ['type' => ['required', 'in:Internal,Customer,Technician'], 'note' => ['required', 'string', 'max:5000']],
            'approval' => ['approval_status' => ['required', 'in:Pending,Approved,Declined'], 'approval_note' => ['nullable', 'string', 'max:2000']],
            'checklist' => ['after' => ['required', 'array', 'max:40'], 'after.*' => ['in:Working,Not working,Not tested']],
            'warranty' => ['warranty_type' => ['required', 'in:None,Days,Months'], 'duration' => ['required', 'integer', 'min:0', 'max:3650'], 'starts_at' => ['nullable', 'date'], 'terms' => ['nullable', 'string', 'max:5000']],
            'photo' => ['images' => ['required', 'array', 'min:1', 'max:'.(int) RepairSettings::get('image_limit', 12)], 'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(int) RepairSettings::get('image_size_kb', 4096)], 'type' => ['required', 'in:Front,Back,Left,Right,Damage,During,After,Other']],
            'photo-delete' => ['image_id' => ['required', 'exists:repair_images,id']],
            default => [],
        };
    }
}
