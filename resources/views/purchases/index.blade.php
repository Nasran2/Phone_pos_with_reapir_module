<x-layouts::app :title="__('Purchases')">
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-slate-850">Purchases</h2>
            <span class="text-xs bg-blue-100 text-blue-800 font-semibold px-2.5 py-0.5 rounded-full">{{ $purchases->total() }} Records</span>
        </div>
        @if(auth()->user()->hasPermission('purchase.create'))
        <a href="{{ route('purchases.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Add Purchase
        </a>
        @endif
    </div>

    <div class="p-6 md:p-8 space-y-6 flex-1 bg-slate-50">
        @if(session('success'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl text-emerald-800 text-xs font-semibold">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl text-red-800 text-xs font-semibold">{{ session('error') }}</div>
        @endif

        <div class="bg-white rounded-xl border border-slate-200/70 p-4 shadow-xs">
            <form method="GET" action="{{ route('purchases.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-3">
                <div class="md:col-span-2 relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search PO, supplier, reference..." class="w-full pl-9 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    <svg class="w-4 h-4 absolute left-3 top-2.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                </div>
                <select name="supplier_id" class="px-3 py-2.5 border border-slate-200 rounded-lg text-sm bg-white focus:ring-1 focus:ring-blue-500">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="px-3 py-2.5 border border-slate-200 rounded-lg text-sm bg-white focus:ring-1 focus:ring-blue-500">
                    <option value="">All Status</option>
                    @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500">
                <div class="md:col-span-6 flex gap-2 justify-end">
                    <a href="{{ route('purchases.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold">Clear</a>
                    <button class="px-4 py-2 bg-slate-850 hover:bg-slate-950 text-white rounded-lg text-xs font-bold">Filter</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/70 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-600">
                        <tr>
                            <th class="px-5 py-4">PO Number</th>
                            <th class="px-5 py-4">Supplier</th>
                            <th class="px-5 py-4">Purchase Date</th>
                            <th class="px-5 py-4">Reference No</th>
                            <th class="px-5 py-4 text-right">Total Items</th>
                            <th class="px-5 py-4 text-right">Grand Total</th>
                            <th class="px-5 py-4 text-right">Paid</th>
                            <th class="px-5 py-4 text-right">Due</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                        @forelse($purchases as $purchase)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4 font-bold text-slate-850">{{ $purchase->displayNo() }}</td>
                            <td class="px-5 py-4">
                                <div class="font-semibold">{{ $purchase->supplier->name ?? '-' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $purchase->supplier->phone ?? '' }}</div>
                            </td>
                            <td class="px-5 py-4">{{ optional($purchase->purchase_date)->format('Y-m-d') }}</td>
                            <td class="px-5 py-4">{{ $purchase->reference_no ?: '-' }}</td>
                            <td class="px-5 py-4 text-right font-semibold">{{ $purchase->items_count }}</td>
                            <td class="px-5 py-4 text-right font-bold">Rs. {{ number_format((float) $purchase->grand_total ?: (float) $purchase->total_amount, 2) }}</td>
                            <td class="px-5 py-4 text-right text-emerald-700 font-bold">Rs. {{ number_format((float) $purchase->paid_amount, 2) }}</td>
                            <td class="px-5 py-4 text-right text-red-600 font-bold">Rs. {{ number_format((float) $purchase->due_amount, 2) }}</td>
                            <td class="px-5 py-4">
                                @php
                                    $statusClass = match($purchase->status) {
                                        'Received' => 'bg-emerald-100 text-emerald-800',
                                        'Ordered', 'Partially Received' => 'bg-blue-100 text-blue-800',
                                        'Cancelled' => 'bg-red-100 text-red-800',
                                        default => 'bg-amber-100 text-amber-800',
                                    };
                                @endphp
                                <span class="px-2 py-1 rounded-full text-[10px] font-bold {{ $statusClass }}">{{ $purchase->status }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-1.5">
                                    <a href="{{ route('purchases.show', $purchase) }}" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100" title="View"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg></a>
                                    @if(auth()->user()->hasPermission('purchase.edit'))
                                    <a href="{{ route('purchases.edit', $purchase) }}" class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50" title="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4v16h16v-7"/><path d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></a>
                                    @endif
                                    @if(auth()->user()->hasPermission('purchase.print'))
                                    <a href="{{ route('purchases.print', $purchase) }}" target="_blank" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100" title="Print"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><path d="M6 14h12v8H6z"/></svg></a>
                                    @endif
                                    @if(auth()->user()->hasPermission('purchase.download'))
                                    <a href="{{ route('purchases.download', $purchase) }}" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100" title="Download PDF"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg></a>
                                    @endif
                                    @if(auth()->user()->hasPermission('purchase.delete'))
                                    <form method="POST" action="{{ route('purchases.destroy', $purchase) }}" onsubmit="return confirmAction(event, 'Delete purchase {{ $purchase->displayNo() }}?')">
                                        @csrf @method('DELETE')
                                        <button class="p-1.5 rounded-lg text-red-600 hover:bg-red-50" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg></button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="px-5 py-12 text-center text-slate-400 font-semibold">No purchases found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">{{ $purchases->links() }}</div>
        </div>
    </div>
</x-layouts::app>
