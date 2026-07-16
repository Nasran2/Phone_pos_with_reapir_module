<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChequeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->input('cheque_type') === 'issued' ? 'cheques.issued.create' : 'cheques.received.create';
        return $this->user()?->hasPermission($permission) ?? false;
    }

    public function rules(): array
    {
        return [
            'cheque_type'=>['required', Rule::in(['received','issued'])], 'cheque_number'=>['required','string','max:100'],
            'cheque_date'=>['required','date'], 'amount'=>['required','numeric','gt:0'], 'bank_name'=>['required','string','max:150'],
            'branch_name'=>['nullable','string','max:150'], 'account_holder'=>['required','string','max:150'],
            'account_number'=>['nullable','string','max:100'], 'customer_id'=>['nullable','exists:customers,id'],
            'supplier_id'=>['nullable','exists:suppliers,id'], 'payee_name'=>['nullable','string','max:255'],
            'sale_id'=>['nullable','exists:sales,id'], 'purchase_id'=>['nullable','exists:purchases,id'],
            'repair_job_id'=>['nullable','integer'], 'expense_id'=>['nullable','exists:expenses,id'],
            'store_id'=>['nullable','exists:stores,id'], 'lorry_id'=>['nullable','exists:lorries,id'],
            'trip_id'=>['nullable','exists:distribution_trips,id'], 'deposit_account_id'=>['nullable','exists:accounts,id'],
            'source_account_id'=>['nullable','exists:accounts,id'], 'attachment'=>[Setting::get('cheque_require_attachment','0') === '1' ? 'required':'nullable','file','mimes:jpg,jpeg,png,pdf','max:5120'],
            'notes'=>['nullable','string','max:2000'],
        ];
    }
}
