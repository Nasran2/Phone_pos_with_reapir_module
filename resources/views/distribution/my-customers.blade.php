<x-layouts::app :title="__('My Lorry Customers')">
<div class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-6">
    <div><h2 class="text-lg font-bold text-slate-850">Lorry Customers</h2><p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ $assignment->lorry_name }} · {{ $assignment->plate_no ?: $assignment->lorry_no }}</p></div>
</div>
<div class="w-full flex-1 space-y-6 overflow-y-auto p-6 md:p-8">
    <form class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-xs">
        <div class="grid grid-cols-1 items-end gap-3 md:grid-cols-[1fr_auto]">
            <input name="q" value="{{ request('q') }}" placeholder="Search customer by name or phone" class="rounded-lg border-slate-200 text-sm">
            <button class="rounded-lg bg-slate-800 px-5 py-2.5 text-xs font-bold text-white hover:bg-slate-950">Search</button>
        </div>
    </form>
    
    <div class="overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-[10px] font-bold uppercase text-slate-500">
                    <tr>
                        <th class="p-4 text-left">Customer</th>
                        <th class="p-4 text-left">Phone</th>
                        <th class="p-4 text-left">Address</th>
                        <th class="p-4 text-right">Balance Due</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customers as $customer)
                    <tr>
                        <td class="p-4 text-xs font-extrabold text-blue-600">{{ $customer->name }}</td>
                        <td class="p-4 text-xs text-slate-600">{{ $customer->phone ?? '-' }}</td>
                        <td class="p-4 text-xs text-slate-600">{{ $customer->address ?? '-' }}</td>
                        <td class="p-4 text-right text-xs font-bold {{ $customer->balance > 0 ? 'text-rose-600' : 'text-slate-600' }}">{{ \App\Support\SystemSettings::money($customer->balance) }}</td>
                        <td class="p-4 text-right">
                            @if($customer->balance > 0)
                                <a href="{{ route('customers.show', $customer->id) }}" class="inline-block rounded bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700 hover:bg-emerald-100 ring-1 ring-inset ring-emerald-600/20">Add Collection</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="p-16 text-center text-xs text-slate-400">No customers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $customers->links() }}</div>
</div>
</x-layouts::app>
