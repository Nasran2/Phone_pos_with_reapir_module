<x-report-layout 
    title="Sales Report" 
    :summary="$summary ?? []" 
    :exportPdf="true" 
    :exportExcel="true" 
    :exportRoute="route('reports.sales')" 
    :resetRoute="route('reports.sales')"
>
    <x-slot name="filterContent">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Quick Date</label>
                <select name="date_filter" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="today" {{ request('date_filter') == 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ request('date_filter') == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="this_week" {{ request('date_filter') == 'this_week' ? 'selected' : '' }}>This Week</option>
                    <option value="last_week" {{ request('date_filter') == 'last_week' ? 'selected' : '' }}>Last Week</option>
                    <option value="this_month" {{ request('date_filter') == 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="last_month" {{ request('date_filter') == 'last_month' ? 'selected' : '' }}>Last Month</option>
                    <option value="this_year" {{ request('date_filter') == 'this_year' ? 'selected' : '' }}>This Year</option>
                    <option value="custom" {{ request('date_filter') == 'custom' ? 'selected' : '' }}>Custom Date Range</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Sale Type</label>
                <select name="sale_type" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="all" {{ request('sale_type') == 'all' ? 'selected' : '' }}>All</option>
                    <option value="retail" {{ request('sale_type') == 'retail' ? 'selected' : '' }}>Retail</option>
                    <option value="wholesale" {{ request('sale_type') == 'wholesale' ? 'selected' : '' }}>Wholesale</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Payment Status</label>
                <select name="payment_status" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">All</option>
                    <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="due" {{ request('payment_status') == 'due' ? 'selected' : '' }}>Due</option>
                </select>
            </div>
        </div>
    </x-slot>

    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-slate-100 text-slate-600 text-xs uppercase tracking-wider border-b border-slate-200">
                <th class="p-4 font-bold">Invoice No</th>
                <th class="p-4 font-bold">Date</th>
                <th class="p-4 font-bold">Customer</th>
                <th class="p-4 font-bold">Type</th>
                <th class="p-4 font-bold text-right">Grand Total</th>
                <th class="p-4 font-bold text-right">Paid</th>
                <th class="p-4 font-bold text-right">Due</th>
                <th class="p-4 font-bold text-center">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
            @forelse($sales as $sale)
                <tr class="hover:bg-slate-50 transition">
                    <td class="p-4 font-medium text-slate-900">{{ $sale->invoice_no }}</td>
                    <td class="p-4">{{ $sale->created_at->format('Y-m-d H:i') }}</td>
                    <td class="p-4">{{ $sale->customer->name ?? 'Walk-in Customer' }}</td>
                    <td class="p-4"><span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $sale->type === 'retail' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">{{ ucfirst($sale->type) }}</span></td>
                    <td class="p-4 text-right font-bold text-slate-900">Rs. {{ number_format($sale->grand_total, 2) }}</td>
                    <td class="p-4 text-right text-emerald-600 font-medium">Rs. {{ number_format($sale->paid_amount, 2) }}</td>
                    <td class="p-4 text-right text-rose-600 font-medium">Rs. {{ number_format($sale->due_amount, 2) }}</td>
                    <td class="p-4 text-center">
                        @if($sale->payment_status === 'paid')
                            <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-md text-xs font-bold">Paid</span>
                        @elseif($sale->payment_status === 'partial')
                            <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-md text-xs font-bold">Partial</span>
                        @else
                            <span class="px-2 py-1 bg-rose-100 text-rose-700 rounded-md text-xs font-bold">Due</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="p-8 text-center text-slate-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                            <p class="font-medium text-slate-600">No sales found for the selected filters.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    @if($sales->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $sales->links() }}
        </div>
    @endif
</x-report-layout>
