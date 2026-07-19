@if($page === 'trips')
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <form method="POST" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 space-y-4">
        @csrf
        <div><h2 class="font-black text-slate-900">Create Trip</h2><p class="text-xs text-slate-500 mt-1">Assign a lorry for daily or route distribution.</p></div>
        <select name="lorry_id" required class="w-full rounded-xl border-slate-200 text-sm"><option value="">Select lorry</option>@foreach($lorries as $lorry)<option value="{{ $lorry->id }}">{{ $lorry->lorry_no }} - {{ $lorry->name }}</option>@endforeach</select>
        <input name="route_name" placeholder="Route name" class="w-full rounded-xl border-slate-200 text-sm">
        <div class="grid grid-cols-2 gap-3">
            <input name="start_date" type="date" value="{{ now()->toDateString() }}" required class="rounded-xl border-slate-200 text-sm">
            <input name="end_date" type="date" class="rounded-xl border-slate-200 text-sm">
        </div>
        <select name="driver_id" class="w-full rounded-xl border-slate-200 text-sm"><option value="">Driver user (optional)</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select>
        <div class="grid grid-cols-2 gap-3">
            <input name="opening_odometer" type="number" min="0" step="any" value="0" placeholder="Opening KM" class="rounded-xl border-slate-200 text-sm">
            <input name="opening_cash" type="number" min="0" step="any" value="0" placeholder="Opening cash" class="rounded-xl border-slate-200 text-sm">
        </div>
        <select name="status" class="w-full rounded-xl border-slate-200 text-sm"><option value="planned">Planned</option><option value="active">Active</option><option value="closed">Closed</option></select>
        <textarea name="notes" rows="3" placeholder="Notes" class="w-full rounded-xl border-slate-200 text-sm"></textarea>
        <button class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white">Save Trip</button>
    </form>
    <section class="xl:col-span-2 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-black text-slate-900">Trips</h2></div>
        <div class="overflow-x-auto"><table class="w-full text-sm">
            <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500"><tr><th class="text-left px-5 py-3">Trip</th><th class="text-left px-5 py-3">Lorry</th><th class="text-left px-5 py-3">Dates</th><th class="text-left px-5 py-3">Status</th><th class="text-right px-5 py-3">Update</th></tr></thead>
            <tbody class="divide-y divide-slate-100">@forelse($trips as $trip)<tr>
                <td class="px-5 py-4 font-bold">{{ $trip->trip_no }}<div class="text-xs text-slate-400">{{ $trip->route_name }}</div></td>
                <td class="px-5 py-4">{{ $trip->lorry_no }}<div class="text-xs text-slate-400">{{ $trip->lorry_name }}</div></td>
                <td class="px-5 py-4">{{ $trip->start_date }}<div class="text-xs text-slate-400">{{ $trip->end_date ?? 'Open' }}</div></td>
                <td class="px-5 py-4"><span class="px-2 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700">{{ ucfirst($trip->status) }}</span></td>
                <td class="px-5 py-4 text-right">
                    <form method="POST" action="{{ route('distribution.trips.status', $trip->id) }}" class="inline-flex gap-2">@csrf
                        <select name="status" class="rounded-lg border-slate-200 text-xs">@foreach(['planned','active','closed','cancelled'] as $status)<option value="{{ $status }}" @selected($trip->status === $status)>{{ ucfirst($status) }}</option>@endforeach</select>
                        <button class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-bold text-white">Save</button>
                    </form>
                </td>
            </tr>@empty<tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">No trips found.</td></tr>@endforelse</tbody>
        </table></div><div class="p-4">{{ $trips->links() }}</div>
    </section>
</div>
@endif

@if($page === 'sale-returns')
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <form method="POST" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 space-y-4">@csrf
        <h2 class="font-black text-slate-900">Record Lorry Return</h2>
        <select name="lorry_id" class="w-full rounded-xl border-slate-200 text-sm"><option value="">Select lorry</option>@foreach($lorries as $lorry)<option value="{{ $lorry->id }}">{{ $lorry->lorry_no }} - {{ $lorry->name }}</option>@endforeach</select>
        <select name="trip_id" class="w-full rounded-xl border-slate-200 text-sm"><option value="">Trip optional</option>@foreach($trips as $trip)<option value="{{ $trip->id }}">{{ $trip->trip_no }}</option>@endforeach</select>
        <input name="return_date" type="date" value="{{ now()->toDateString() }}" required class="w-full rounded-xl border-slate-200 text-sm">
        <input name="amount" type="number" min="0" step="any" placeholder="Return amount" required class="w-full rounded-xl border-slate-200 text-sm">
        <select name="status" class="w-full rounded-xl border-slate-200 text-sm"><option value="draft">Draft</option><option value="approved">Approved</option><option value="completed">Completed</option></select>
        <textarea name="reason" rows="3" placeholder="Reason" class="w-full rounded-xl border-slate-200 text-sm"></textarea>
        <button class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white">Save Return</button>
    </form>
    <section class="xl:col-span-2 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-black text-slate-900">Returns</h2></div>
        <table class="w-full text-sm"><thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500"><tr><th class="text-left px-5 py-3">Return</th><th class="text-left px-5 py-3">Lorry</th><th class="text-right px-5 py-3">Amount</th><th class="text-left px-5 py-3">Status</th></tr></thead>
        <tbody class="divide-y divide-slate-100">@forelse($returns as $return)<tr><td class="px-5 py-4 font-bold">{{ $return->return_no }}</td><td class="px-5 py-4">{{ $return->lorry_no ?? '-' }}</td><td class="px-5 py-4 text-right font-bold">{{ $money($return->amount) }}</td><td class="px-5 py-4">{{ ucfirst($return->status) }}</td></tr>@empty<tr><td colspan="4" class="px-5 py-10 text-center text-slate-400">No returns found.</td></tr>@endforelse</tbody></table>
        <div class="p-4">{{ $returns->links() }}</div>
    </section>
</div>
@endif

@if($page === 'expenses')
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <form method="POST" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 space-y-4">@csrf
        <h2 class="font-black text-slate-900">Add Lorry Expense</h2>
        <input name="title" required placeholder="Expense title" class="w-full rounded-xl border-slate-200 text-sm">
        <input name="expense_date" type="date" value="{{ now()->toDateString() }}" required class="w-full rounded-xl border-slate-200 text-sm">
        <input name="amount" type="number" min="0" step="any" required placeholder="Amount" class="w-full rounded-xl border-slate-200 text-sm">
        <select name="payment_method" required class="w-full rounded-xl border-slate-200 text-sm">@forelse($paymentMethods as $method)<option value="{{ $method->name }}">{{ $method->name }}</option>@empty<option value="Cash">Cash</option>@endforelse</select>
        <select name="lorry_id" class="w-full rounded-xl border-slate-200 text-sm"><option value="">Lorry optional</option>@foreach($lorries as $lorry)<option value="{{ $lorry->id }}">{{ $lorry->lorry_no }} - {{ $lorry->name }}</option>@endforeach</select>
        <select name="trip_id" class="w-full rounded-xl border-slate-200 text-sm"><option value="">Trip optional</option>@foreach($trips as $trip)<option value="{{ $trip->id }}">{{ $trip->trip_no }}</option>@endforeach</select>
        <textarea name="description" rows="3" placeholder="Description" class="w-full rounded-xl border-slate-200 text-sm"></textarea>
        <button class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white">Save Expense</button>
    </form>
    <section class="xl:col-span-2 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-black text-slate-900">Lorry Expenses</h2></div>
        <table class="w-full text-sm"><thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500"><tr><th class="text-left px-5 py-3">Expense</th><th class="text-left px-5 py-3">Lorry / Trip</th><th class="text-left px-5 py-3">Date</th><th class="text-right px-5 py-3">Amount</th></tr></thead>
        <tbody class="divide-y divide-slate-100">@forelse($expenses as $expense)<tr><td class="px-5 py-4 font-bold">{{ $expense->title }}<div class="text-xs text-slate-400">{{ $expense->expense_no }}</div></td><td class="px-5 py-4">{{ $expense->lorry_no ?? '-' }}<div class="text-xs text-slate-400">{{ $expense->trip_no }}</div></td><td class="px-5 py-4">{{ $expense->expense_date }}</td><td class="px-5 py-4 text-right font-bold">{{ $money($expense->amount) }}</td></tr>@empty<tr><td colspan="4" class="px-5 py-10 text-center text-slate-400">No lorry expenses yet.</td></tr>@endforelse</tbody></table>
        <div class="p-4">{{ $expenses->links() }}</div>
    </section>
</div>
@endif

@if($page === 'collections')
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <form method="POST" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 space-y-4">@csrf
        <h2 class="font-black text-slate-900">Record Collection</h2>
        <select name="lorry_id" required class="w-full rounded-xl border-slate-200 text-sm"><option value="">Select lorry</option>@foreach($lorries as $lorry)<option value="{{ $lorry->id }}">{{ $lorry->lorry_no }} - {{ $lorry->name }}</option>@endforeach</select>
        <select name="trip_id" class="w-full rounded-xl border-slate-200 text-sm"><option value="">Trip optional</option>@foreach($trips as $trip)<option value="{{ $trip->id }}">{{ $trip->trip_no }}</option>@endforeach</select>
        <input name="collection_date" type="date" value="{{ now()->toDateString() }}" required class="w-full rounded-xl border-slate-200 text-sm">
        <input name="amount" type="number" min="0.01" step="any" required placeholder="Amount" class="w-full rounded-xl border-slate-200 text-sm">
        <select name="payment_method" required class="w-full rounded-xl border-slate-200 text-sm">@forelse($paymentMethods as $method)<option value="{{ $method->name }}">{{ $method->name }}</option>@empty<option value="Cash">Cash</option>@endforelse</select>
        <input name="reference_no" placeholder="Reference no." class="w-full rounded-xl border-slate-200 text-sm">
        <textarea name="notes" rows="3" placeholder="Notes" class="w-full rounded-xl border-slate-200 text-sm"></textarea>
        <button class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white">Save Collection</button>
    </form>
    <section class="xl:col-span-2 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-black text-slate-900">Collections</h2></div>
        <table class="w-full text-sm"><thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500"><tr><th class="text-left px-5 py-3">Collection</th><th class="text-left px-5 py-3">Lorry / Trip</th><th class="text-left px-5 py-3">Method</th><th class="text-right px-5 py-3">Amount</th></tr></thead>
        <tbody class="divide-y divide-slate-100">@forelse($collections as $collection)<tr><td class="px-5 py-4 font-bold">{{ $collection->collection_no }}<div class="text-xs text-slate-400">{{ $collection->collection_date }}</div></td><td class="px-5 py-4">{{ $collection->lorry_no }}<div class="text-xs text-slate-400">{{ $collection->trip_no }}</div></td><td class="px-5 py-4">{{ $collection->payment_method }}</td><td class="px-5 py-4 text-right font-bold">{{ $money($collection->amount) }}</td></tr>@empty<tr><td colspan="4" class="px-5 py-10 text-center text-slate-400">No collections yet.</td></tr>@endforelse</tbody></table>
        <div class="p-4">{{ $collections->links() }}</div>
    </section>
</div>
@endif

@if($page === 'reconciliation')
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <form method="POST" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 space-y-4">@csrf
        <h2 class="font-black text-slate-900">Daily Reconciliation</h2>
        <select name="lorry_id" required class="w-full rounded-xl border-slate-200 text-sm"><option value="">Select lorry</option>@foreach($lorries as $lorry)<option value="{{ $lorry->id }}">{{ $lorry->lorry_no }} - {{ $lorry->name }}</option>@endforeach</select>
        <select name="trip_id" class="w-full rounded-xl border-slate-200 text-sm"><option value="">Trip optional</option>@foreach($trips as $trip)<option value="{{ $trip->id }}">{{ $trip->trip_no }}</option>@endforeach</select>
        <input name="reconciliation_date" type="date" value="{{ now()->toDateString() }}" required class="w-full rounded-xl border-slate-200 text-sm">
        <input name="counted_cash" type="number" min="0" step="any" required placeholder="Counted cash" class="w-full rounded-xl border-slate-200 text-sm">
        <textarea name="notes" rows="3" placeholder="Notes" class="w-full rounded-xl border-slate-200 text-sm"></textarea>
        <button class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white">Close Day</button>
    </form>
    <section class="xl:col-span-2 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-black text-slate-900">Reconciliations</h2></div>
        <table class="w-full text-sm"><thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500"><tr><th class="text-left px-5 py-3">No</th><th class="text-left px-5 py-3">Lorry</th><th class="text-right px-5 py-3">Expected</th><th class="text-right px-5 py-3">Counted</th><th class="text-right px-5 py-3">Variance</th></tr></thead>
        <tbody class="divide-y divide-slate-100">@forelse($reconciliations as $row)<tr><td class="px-5 py-4 font-bold">{{ $row->reconciliation_no }}<div class="text-xs text-slate-400">{{ $row->reconciliation_date }}</div></td><td class="px-5 py-4">{{ $row->lorry_no }}<div class="text-xs text-slate-400">{{ $row->trip_no }}</div></td><td class="px-5 py-4 text-right">{{ $money($row->expected_cash) }}</td><td class="px-5 py-4 text-right">{{ $money($row->counted_cash) }}</td><td class="px-5 py-4 text-right font-bold {{ $row->variance < 0 ? 'text-rose-600' : 'text-emerald-700' }}">{{ $money($row->variance) }}</td></tr>@empty<tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">No reconciliations yet.</td></tr>@endforelse</tbody></table>
        <div class="p-4">{{ $reconciliations->links() }}</div>
    </section>
</div>
@endif

@if($page === 'settings')
<form method="POST" class="max-w-4xl bg-white border border-slate-200 rounded-2xl shadow-sm p-6 space-y-5">@csrf
    <h2 class="font-black text-slate-900">Distribution Settings</h2>
    @foreach([
        'enable_distribution_module' => 'Enable Distribution Module',
        'allow_lorry_sales_without_trip' => 'Allow Lorry Sales Without Trip',
        'require_lorry_reconciliation' => 'Require Daily Reconciliation',
        'prevent_negative_lorry_stock' => 'Prevent Negative Lorry Stock',
        'lorry_agent_can_view_all_customers' => 'Lorry Agents Can View ALL Global Customers',
    ] as $key => $label)
        <label class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
            <span class="font-bold text-slate-800">{{ $label }}</span>
            <input type="checkbox" name="{{ $key }}" value="1" @checked($settings[$key]) class="rounded border-slate-300 text-blue-600">
        </label>
    @endforeach
    <label class="block text-sm font-bold text-slate-700">Default Lorry Sale Payment Method
        <input name="default_lorry_sale_payment_method" value="{{ $settings['default_lorry_sale_payment_method'] }}" class="mt-2 w-full rounded-xl border-slate-200 text-sm">
    </label>
    <button class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white">Save Settings</button>
</form>
@endif
