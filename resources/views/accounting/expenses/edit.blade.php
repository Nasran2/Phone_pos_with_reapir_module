<x-layouts::app :title="__('Edit Expense')">
<div class="p-4 sm:p-6 max-w-3xl mx-auto">
    <h1 class="text-xl font-bold mb-5">Edit expense {{ $expense->expense_no }}</h1>
    <form method="POST" action="{{ route('accounting.expenses.update', $expense) }}" class="bg-white rounded-xl border p-4 space-y-4">
        @csrf @method('PUT')
        @foreach(['expense_date' => 'Date', 'title' => 'Title', 'amount' => 'Amount', 'reference_no' => 'Reference'] as $field => $label)
            <label class="block">{{ $label }}
                <input class="block w-full rounded-lg border mt-1" name="{{ $field }}"
                    type="{{ $field === 'expense_date' ? 'date' : ($field === 'amount' ? 'number' : 'text') }}"
                    @if($field === 'amount') step="0.01" min="0.01" @endif
                    value="{{ old($field, $field === 'expense_date' ? $expense->expense_date->format('Y-m-d') : $expense->$field) }}"
                    @required($field !== 'reference_no')>
            </label>
        @endforeach
        <label class="block">Payment method
            <select name="payment_method" class="block w-full rounded-lg border mt-1" required>
                @foreach(array_unique([$expense->payment_method, 'Cash', 'Bank Transfer', 'Cheque', 'Online Payment', 'Other']) as $method)
                    <option @selected(old('payment_method', $expense->payment_method) === $method)>{{ $method }}</option>
                @endforeach
            </select>
        </label>
        <label class="block">Category<select name="expense_category_id" class="block w-full rounded-lg border mt-1" required>
            @foreach($categories as $option)<option value="{{ $option->id }}" @selected(old('expense_category_id', $expense->expense_category_id) == $option->id)>{{ $option->name }}</option>@endforeach
        </select></label>
        <label class="block">Expense account<select name="expense_account_id" class="block w-full rounded-lg border mt-1" required>
            @foreach($expenseAccounts as $option)<option value="{{ $option->id }}" @selected(old('expense_account_id', $expenseAccountId) == $option->id)>{{ $option->account_name }}</option>@endforeach
        </select></label>
        <label class="block">Paid from<select name="paid_from_account_id" class="block w-full rounded-lg border mt-1" required>
            @foreach($paymentAccounts as $option)<option value="{{ $option->id }}" @selected(old('paid_from_account_id', $expense->paid_from_account_id) == $option->id)>{{ $option->account_name }}</option>@endforeach
        </select></label>
        <label class="block">Description<textarea name="description" class="block w-full rounded-lg border mt-1">{{ old('description', $expense->description) }}</textarea></label>
        <div class="flex gap-3"><button class="px-4 py-3 rounded-lg bg-blue-600 text-white">Save changes</button><a class="px-4 py-3" href="{{ route('accounting.expenses.index') }}">Cancel</a></div>
    </form>
</div>
</x-layouts::app>
