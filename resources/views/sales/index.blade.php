<x-layouts::app :title="__('Sales Ledger')">
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-slate-850">Sales Ledger</h2>
            <span class="text-xs bg-blue-100 text-blue-800 font-semibold px-2.5 py-0.5 rounded-full">{{ $sales->total() }} Sales</span>
        </div>
        @if(auth()->user()->hasPermission('sales_return.create'))
        <a href="{{ route('sales-returns.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            New Return
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

        <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-6 gap-4">
            @foreach([
                ['Today Sales', $summary['today_sales'], 'blue'],
                ['Retail Sales', $summary['retail_sales'], 'emerald'],
                ['Wholesale Sales', $summary['wholesale_sales'], 'indigo'],
                ['Due Sales', $summary['due_sales'], 'red'],
                ['Total Profit', $summary['total_profit'], 'amber'],
                ['Pending Orders', $summary['pending_orders'], 'orange'],
            ] as [$label, $amount, $color])
            <div class="bg-white border border-slate-200/70 rounded-xl p-4 shadow-xs">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ $label }}</p>
                <p class="mt-2 text-lg font-black text-slate-850">{{ $label === 'Pending Orders' ? number_format((int) $amount) : 'Rs. '.number_format((float) $amount, 2) }}</p>
            </div>
            @endforeach
        </div>

        <div class="bg-white rounded-xl border border-slate-200/70 p-4 shadow-xs">
            <form method="GET" action="{{ route('sales.index') }}" class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-9 gap-3">
                <div class="md:col-span-2 relative">
                    <input name="search" value="{{ request('search') }}" placeholder="Invoice, customer, phone..." class="w-full pl-9 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    <svg class="w-4 h-4 absolute left-3 top-2.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                </div>
                <select name="sale_type" class="px-3 py-2.5 border border-slate-200 rounded-lg text-sm bg-white">
                    <option value="">All Types</option>
                    <option value="retail" @selected(request('sale_type') === 'retail')>Retail</option>
                    <option value="wholesale" @selected(request('sale_type') === 'wholesale')>Wholesale</option>
                </select>
                <label class="flex items-center gap-2 px-3 py-2.5 border border-slate-200 rounded-lg text-xs font-semibold bg-white">
                    <input type="checkbox" name="pending_only" value="1" @checked(request()->boolean('pending_only'))>
                    Pending inventory
                </label>
                <select name="payment_status" class="px-3 py-2.5 border border-slate-200 rounded-lg text-sm bg-white">
                    <option value="">All Status</option>
                    @foreach(['Paid', 'Partial', 'Due', 'Cheque Hold', 'Partial + Cheque Hold', 'Partial Paid + Cheque Hold', 'Partial + Cheque Hold + Due', 'Cheque Hold + Due', 'Refunded', 'Cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('payment_status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                <select name="cashier_id" class="px-3 py-2.5 border border-slate-200 rounded-lg text-sm bg-white">
                    <option value="">All Cashiers</option>
                    @foreach($cashiers as $cashier)
                    <option value="{{ $cashier->id }}" @selected(request('cashier_id') == $cashier->id)>{{ $cashier->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-3 py-2.5 border border-slate-200 rounded-lg text-sm">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3 py-2.5 border border-slate-200 rounded-lg text-sm">
                <div class="flex gap-2 justify-end">
                    <a href="{{ route('sales.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold">Clear</a>
                    <button class="px-4 py-2 bg-slate-850 hover:bg-slate-950 text-white rounded-lg text-xs font-bold">Filter</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/70 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-600">
                        <tr>
                            <th class="px-4 py-4">Invoice No</th>
                            <th class="px-4 py-4">Date & Time</th>
                            <th class="px-4 py-4">Customer</th>
                            <th class="px-4 py-4">Type</th>
                            <th class="px-4 py-4 text-right">Items</th>
                            <th class="px-4 py-4 text-right">Subtotal</th>
                            <th class="px-4 py-4 text-right">Discount</th>
                            <th class="px-4 py-4 text-right">Grand Total</th>
                            <th class="px-4 py-4 text-right">Paid</th>
                            <th class="px-4 py-4 text-right">Due</th>
                            <th class="px-4 py-4">Payment</th>
                            <th class="px-4 py-4">Inventory</th>
                            <th class="px-4 py-4">Cashier</th>
                            <th class="px-4 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                        @forelse($sales as $sale)
                        @php
                            $status = $sale->displayStatus();
                            $statusClass = match($status) {
                                'Paid' => 'bg-emerald-100 text-emerald-800',
                                'Partial' => 'bg-amber-100 text-amber-800',
                                'Refunded' => 'bg-blue-100 text-blue-800',
                                'Cancelled' => 'bg-slate-200 text-slate-700',
                                default => 'bg-red-100 text-red-800',
                            };
                            $typeClass = $sale->pos_type === 'wholesale' ? 'bg-indigo-100 text-indigo-800' : 'bg-blue-100 text-blue-800';
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-4 font-bold text-slate-850">{{ $sale->invoice_no }}</td>
                            <td class="px-4 py-4">{{ $sale->created_at->format('Y-m-d h:i A') }}</td>
                            <td class="px-4 py-4">
                                <div class="font-semibold">{{ $sale->customer->name ?? 'Walk-in Customer' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $sale->customer->phone ?? '' }}</div>
                            </td>
                            <td class="px-4 py-4"><span class="px-2 py-1 rounded-full text-[10px] font-bold {{ $typeClass }}">{{ ucfirst($sale->pos_type) }}</span></td>
                            <td class="px-4 py-4 text-right font-semibold">{{ $sale->items_count }}</td>
                            <td class="px-4 py-4 text-right">Rs. {{ number_format((float) $sale->total_amount, 2) }}</td>
                            <td class="px-4 py-4 text-right">Rs. {{ number_format((float) $sale->discount_amount, 2) }}</td>
                            <td class="px-4 py-4 text-right font-black text-slate-850">Rs. {{ number_format((float) $sale->payable_amount, 2) }}</td>
                            <td class="px-4 py-4 text-right text-emerald-700 font-bold">Rs. {{ number_format((float) $sale->paid_amount, 2) }}</td>
                            <td class="px-4 py-4 text-right text-red-600 font-bold">Rs. {{ number_format((float) $sale->due_amount, 2) }}</td>
                            <td class="px-4 py-4"><span class="px-2 py-1 rounded-full text-[10px] font-bold {{ $statusClass }}">{{ $status }}</span></td>
                            <td class="px-4 py-4"><span class="px-2 py-1 rounded-full text-[10px] font-bold {{ $sale->stock_deducted_at ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ $sale->inventoryStatus() }}</span></td>
                            <td class="px-4 py-4">{{ $sale->cashier->name ?? '-' }}</td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-1.5">
                                    @if(auth()->user()->hasPermission('sales.invoice_view'))
                                    <a href="{{ route('sales.show', $sale) }}" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100" title="View"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg></a>
                                    @endif
                                    @if(auth()->user()->hasPermission('sales.print'))
                                    <a href="{{ route('sales.print', $sale) }}" target="_blank" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100" title="Print"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><path d="M6 14h12v8H6z"/></svg></a>
                                    @endif
                                    @if(auth()->user()->hasPermission('sales.pdf'))
                                    <a href="{{ route('sales.download', $sale) }}" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100" title="PDF"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg></a>
                                    @endif
                                    @if(auth()->user()->hasPermission('sales_return.create') && $sale->stock_deducted_at)
                                    <a href="{{ route('sales-returns.create', ['sale_id' => $sale->id]) }}" class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-50" title="Return"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 14l-4-4 4-4"/><path d="M5 10h11a4 4 0 010 8h-1"/></svg></a>
                                    @endif
                                    @if(auth()->user()->hasPermission('sales.delete'))
                                    <form method="POST" action="{{ route('sales.destroy', $sale) }}" onsubmit="return confirmAction(event, 'Delete sale {{ $sale->invoice_no }}?')">
                                        @csrf @method('DELETE')
                                        <button class="p-1.5 rounded-lg text-red-600 hover:bg-red-50" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg></button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="14" class="px-5 py-12 text-center text-slate-400 font-semibold">No sales found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">{{ $sales->links() }}</div>
        </div>
    </div>
</x-layouts::app>
