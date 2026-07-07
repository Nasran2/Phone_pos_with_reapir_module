<x-report-layout 
    title="Low Stock Report" 
    :summary="$summary ?? []" 
    :chartData="$chartData ?? []" 
    :exportPdf="true" 
    :exportExcel="true" 
    :exportRoute="route('reports.low_stock')" 
    :resetRoute="route('reports.low_stock')"
>
    <x-slot name="filterContent">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Quick Date</label>
                <select name="date_filter" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="today" {{ request('date_filter', 'today') == 'today' ? 'selected' : '' }}>Today</option>
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
        </div>
    </x-slot>

    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-slate-100 text-slate-600 text-xs uppercase tracking-wider border-b border-slate-200">
                <th class="p-4 font-bold">Product</th>
                <th class="p-4 font-bold">Stock</th>
                <th class="p-4 font-bold">Alert Qty</th>

            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
            @forelse($products ?? [] as $item)
                <tr class="hover:bg-slate-50 transition">
                    <td class="p-4">{{ $item->name }}</td>
                    <td class="p-4">{{ $item->stock->quantity ?? 0 }}</td>
                    <td class="p-4">{{ $item->stock_alert_quantity }}</td>

                </tr>
            @empty
                <tr>
                    <td colspan="3" class="p-8 text-center text-slate-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                            <p class="font-medium text-slate-600">No records found for the selected filters.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    @if(isset($products) && method_exists($products, 'hasPages') && $products->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $products->links() }}
        </div>
    @endif
</x-report-layout>