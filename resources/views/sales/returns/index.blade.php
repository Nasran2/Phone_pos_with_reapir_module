<x-layouts::app :title="__('Sales Returns')">
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-black">Sales Returns</h2>
            <span class="text-xs bg-blue-100 text-blue-800 font-semibold px-2.5 py-0.5 rounded-full">{{ $returns->total() }} Returns</span>
        </div>
        @if(auth()->user()->hasPermission('sales_return.create'))
        <a href="{{ route('sales-returns.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Create Return
        </a>
        @endif
    </div>

    <div class="p-6 md:p-8 space-y-6 flex-1 bg-slate-50">
        @if(session('success'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl text-emerald-800 text-xs font-semibold">{{ session('success') }}</div>
        @endif

        <div class="bg-white rounded-xl border border-slate-200/70 p-4 shadow-xs">
            <form method="GET" action="{{ route('sales-returns.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-3">
                <input name="search" value="{{ request('search') }}" placeholder="Return, invoice, customer..." class="md:col-span-2 px-3 py-2.5 border border-slate-900 rounded-lg text-sm">
                <select name="sale_type" class="px-3 py-2.5 border border-slate-900 rounded-lg text-sm bg-white">
                    <option value="">All Types</option>
                    <option value="retail" @selected(request('sale_type') === 'retail')>Retail</option>
                    @if(\App\Support\Features::wholesaleEnabled())<option value="wholesale" @selected(request('sale_type') === 'wholesale')>Wholesale</option>@endif
                </select>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-3 py-2.5 border border-slate-900 rounded-lg text-sm">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3 py-2.5 border border-slate-900 rounded-lg text-sm">
                <div class="flex gap-2 justify-end">
                    <a href="{{ route('sales-returns.index') }}" class="flex items-center justify-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-black rounded-lg text-xs font-bold">Clear</a>
                    <button class="flex items-center justify-center px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-bold">Filter</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/70 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase tracking-wider text-black">
                        <tr>
                            <th class="px-5 py-4">Return No</th>
                            <th class="px-5 py-4">Invoice No</th>
                            <th class="px-5 py-4">Customer</th>
                            <th class="px-5 py-4">Sale Type</th>
                            <th class="px-5 py-4">Return Date</th>
                            <th class="px-5 py-4 text-right">Items</th>
                            <th class="px-5 py-4 text-right">Return Amount</th>
                            <th class="px-5 py-4">Refund Status</th>
                            <th class="px-5 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-black">
                        @forelse($returns as $return)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4 font-bold text-black">{{ $return->return_no }}</td>
                            <td class="px-5 py-4">{{ $return->sale->invoice_no ?? '-' }}</td>
                            <td class="px-5 py-4">{{ $return->customer->name ?? '-' }}</td>
                            <td class="px-5 py-4"><span class="px-2 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">{{ ucfirst($return->sale->pos_type ?? '-') }}</span></td>
                            <td class="px-5 py-4">{{ optional($return->return_date)->format('Y-m-d') }}</td>
                            <td class="px-5 py-4 text-right">{{ $return->items_count }}</td>
                            <td class="px-5 py-4 text-right font-black">Rs. {{ number_format((float) $return->total_amount, 2) }}</td>
                            <td class="px-5 py-4"><span class="px-2 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">{{ $return->refund_status }}</span></td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-1.5">
                                    <a href="{{ route('sales-returns.show', $return) }}" class="p-1.5 rounded-lg text-black hover:bg-slate-100" title="View"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg></a>
                                    @if(auth()->user()->hasPermission('sales_return.delete'))
                                    <form method="POST" action="{{ route('sales-returns.destroy', $return) }}" onsubmit="return confirmAction(event, 'Delete return {{ $return->return_no }}?')">
                                        @csrf @method('DELETE')
                                        <button class="p-1.5 rounded-lg text-red-600 hover:bg-red-50" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg></button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="px-5 py-12 text-center text-black font-semibold">No sale returns found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">{{ $returns->links() }}</div>
        </div>
    </div>
</x-layouts::app>
