<x-layouts::app :title="__('Hold Sales')">
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-slate-850">Hold Sales</h2>
            <span class="text-xs bg-blue-100 text-blue-800 font-semibold px-2.5 py-0.5 rounded-full">{{ $holds->total() }} Holds</span>
        </div>
    </div>

    <div class="p-6 md:p-8 space-y-6 flex-1 bg-slate-50">
        @if(session('success'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl text-emerald-800 text-xs font-semibold">{{ session('success') }}</div>
        @endif

        <div class="bg-white rounded-xl border border-slate-200/70 p-4 shadow-xs">
            <form method="GET" action="{{ route('hold-sales.index') }}" class="grid grid-cols-1 md:grid-cols-7 gap-3">
                <input name="search" value="{{ request('search') }}" placeholder="Hold no, customer, cashier..." class="md:col-span-2 px-3 py-2.5 border border-slate-200 rounded-lg text-sm">
                <select name="sale_type" class="px-3 py-2.5 border border-slate-200 rounded-lg text-sm bg-white">
                    <option value="">All Types</option>
                    <option value="retail" @selected(request('sale_type') === 'retail')>Retail</option>
                    <option value="wholesale" @selected(request('sale_type') === 'wholesale')>Wholesale</option>
                </select>
                <select name="cashier_id" class="px-3 py-2.5 border border-slate-200 rounded-lg text-sm bg-white">
                    <option value="">All Cashiers</option>
                    @foreach($cashiers as $cashier)
                    <option value="{{ $cashier->id }}">{{ $cashier->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-3 py-2.5 border border-slate-200 rounded-lg text-sm">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3 py-2.5 border border-slate-200 rounded-lg text-sm">
                <div class="flex gap-2 justify-end">
                    <a href="{{ route('hold-sales.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold">Clear</a>
                    <button class="px-4 py-2 bg-slate-850 hover:bg-slate-950 text-white rounded-lg text-xs font-bold">Filter</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/70 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-600">
                        <tr>
                            <th class="px-5 py-4">Hold No</th>
                            <th class="px-5 py-4">Date & Time</th>
                            <th class="px-5 py-4">Customer</th>
                            <th class="px-5 py-4">Sale Type</th>
                            <th class="px-5 py-4 text-right">Items</th>
                            <th class="px-5 py-4 text-right">Estimated Total</th>
                            <th class="px-5 py-4">Cashier</th>
                            <th class="px-5 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                        @forelse($holds as $hold)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4 font-bold text-slate-850">{{ $hold->reference_no }}</td>
                            <td class="px-5 py-4">{{ $hold->created_at->format('Y-m-d h:i A') }}</td>
                            <td class="px-5 py-4">{{ $hold->customer->name ?? 'Walk-in Customer' }}</td>
                            <td class="px-5 py-4"><span class="px-2 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">{{ ucfirst($hold->pos_type) }}</span></td>
                            <td class="px-5 py-4 text-right">{{ $hold->items_count }}</td>
                            <td class="px-5 py-4 text-right font-black">Rs. {{ number_format((float) ($hold->estimated_total ?: $hold->total_amount), 2) }}</td>
                            <td class="px-5 py-4">{{ $hold->cashier->name ?? '-' }}</td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-1.5">
                                    <a href="{{ route('hold-sales.show', $hold) }}" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100" title="View"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg></a>
                                    @if(auth()->user()->hasPermission('hold_sales.resume'))
                                    <form method="POST" action="{{ route('hold-sales.resume', $hold) }}">
                                        @csrf
                                        <button class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50" title="Resume"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg></button>
                                    </form>
                                    @endif
                                    @if(auth()->user()->hasPermission('hold_sales.delete'))
                                    <form method="POST" action="{{ route('hold-sales.destroy', $hold) }}" onsubmit="return confirm('Delete hold {{ $hold->reference_no }}?')">
                                        @csrf @method('DELETE')
                                        <button class="p-1.5 rounded-lg text-red-600 hover:bg-red-50" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg></button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="px-5 py-12 text-center text-slate-400 font-semibold">No held sales found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">{{ $holds->links() }}</div>
        </div>
    </div>
</x-layouts::app>
