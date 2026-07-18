<x-layouts::app title="VAT Return Summary">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">VAT Return Summary</h1>
            <p class="text-sm text-slate-500">Summary of Output and Input VAT for filing purposes.</p>
        </div>
        <a href="{{ route('tax.reports.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Back to Reports</a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Filing Month</th>
                        <th class="px-4 py-3 font-medium text-right">Output VAT (Sales)</th>
                        <th class="px-4 py-3 font-medium text-right">Input VAT (Purchases)</th>
                        <th class="px-4 py-3 font-medium text-right">Import Tax</th>
                        <th class="px-4 py-3 font-medium text-right">Net VAT Liability</th>
                        <th class="px-4 py-3 font-medium text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($periods as $period)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $period->period_name }}</td>
                        <td class="px-4 py-3 text-right">Rs. {{ number_format($period->output_tax, 2) }}</td>
                        <td class="px-4 py-3 text-right">Rs. {{ number_format($period->input_tax, 2) }}</td>
                        <td class="px-4 py-3 text-right">Rs. {{ number_format($period->import_tax, 2) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-slate-900">Rs. {{ number_format($period->total_liability, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($period->payment_status === 'Paid')
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold uppercase">Paid</span>
                            @elseif($period->payment_status === 'Partially Paid')
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-semibold uppercase">Partial</span>
                            @elseif($period->payment_status === 'Not Due')
                                <span class="px-2 py-1 bg-slate-100 text-slate-700 rounded-full text-xs font-semibold uppercase">Not Due</span>
                            @else
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold uppercase">Unpaid</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">No data available.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200">
            {{ $periods->links() }}
        </div>
    </div>
</x-layouts::app>
