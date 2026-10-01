<x-layouts::app :title="__('Edit Account')">
<div class="p-4 sm:p-6 max-w-3xl mx-auto">
<h1 class="text-xl font-bold mb-5">Edit account</h1>
<form method="POST" action="{{ route('accounting.accounts.update', $account) }}" class="bg-white rounded-xl border p-4 space-y-4">
@csrf @method('PUT')
@foreach(['account_code' => 'Code', 'account_name' => 'Name', 'description' => 'Description'] as $field => $label)
<label class="block">{{ $label }}<input name="{{ $field }}" class="block w-full rounded-lg border mt-1" value="{{ old($field, $account->$field) }}" @required($field !== 'description')></label>
@endforeach
<p class="text-sm text-black">Account type: {{ $account->account_type }}. Balances are maintained by ledger transactions.</p>
<label class="block">Status<select name="status" class="block w-full rounded-lg border mt-1"><option value="1" @selected(old('status', $account->status) == 1)>Active</option><option value="0" @selected(old('status', $account->status) == 0)>Inactive</option></select></label>
<div class="flex gap-3"><button class="px-4 py-3 rounded-lg bg-blue-600 text-white">Save changes</button><a class="px-4 py-3" href="{{ route('accounting.accounts.index') }}">Cancel</a></div>
</form></div>
</x-layouts::app>
