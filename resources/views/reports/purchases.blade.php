<x-report-layout 
    title="Purchases Report" 
    :summary="$summary ?? []" 
    :chartData="$chartData ?? []" 
    :exportPdf="true" 
    :exportExcel="true" 
    :exportRoute="route('reports.purchases')" 
    :resetRoute="route('reports.purchases')"
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
            <!-- More filters can be added here if suppliers exist -->
        </div>
    </x-slot>

    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-slate-100 text-slate-600 text-xs uppercase tracking-wider border-b border-slate-200">
                <th class="p-4 font-bold">Purchase No</th>
                <th class="p-4 font-bold">Date</th>
                <th class="p-4 font-bold">Supplier</th>
                <th class="p-4 font-bold text-right">Grand Total</th>
                <th class="p-4 font-bold text-right">Paid</th>
                <th class="p-4 font-bold text-right">Due</th>
                <th class="p-4 font-bold text-center">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
            @forelse($purchases as $purchase)
                <tr class="hover:bg-slate-50 transition">
                    <td class="p-4 font-medium text-slate-900">{{ $purchase->purchase_no }}</td>
                    <td class="p-4">{{ $purchase->created_at->format('Y-m-d H:i') }}</td>
                    <td class="p-4">{{ $purchase->supplier->name ?? 'Unknown Supplier' }}</td>
                    <td class="p-4 text-right font-bold text-slate-900">Rs. {{ number_format((float) ($purchase->grand_total ?? $purchase->total_amount ?? $purchase->subtotal ?? 0), 2) }}</td>
                    <td class="p-4 text-right text-emerald-600 font-medium">Rs. {{ number_format($purchase->paid_amount, 2) }}</td>
                    <td class="p-4 text-right text-rose-600 font-medium">Rs. {{ number_format($purchase->due_amount, 2) }}</td>
                    <td class="p-4 text-center">
                        <span class="px-2 py-1 bg-slate-100 text-slate-700 rounded-md text-xs font-bold">{{ ucfirst($purchase->status) }}</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="p-8 text-center text-slate-500">No purchases found.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($purchases->hasPages())
        <div class="p-4 border-t border-slate-200">{{ $purchases->links() }}</div>
    @endif
</x-report-layout>
