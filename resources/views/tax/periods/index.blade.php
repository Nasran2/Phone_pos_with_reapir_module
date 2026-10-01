<x-layouts::app title="Monthly Tax Balances">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black">Monthly Tax Balances</h1>
            <p class="text-sm text-black">Manage tax reporting periods, view liabilities, and carry forward balances.</p>
        </div>
    </div>

    <!-- Alert -->
    @if(session('success'))
    <div class="mb-4 bg-green-50 text-green-800 p-4 rounded-lg text-sm border border-green-200">
        {{ session('success') }}
    </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 border-b border-slate-200 text-black">
                    <tr>
                        <th class="px-4 py-3 font-medium">Period</th>
                        <th class="px-4 py-3 font-medium">Date Range</th>
                        <th class="px-4 py-3 font-medium text-right">Opening Bal.</th>
                        <th class="px-4 py-3 font-medium text-right">Net VAT</th>
                        <th class="px-4 py-3 font-medium text-right">Adjustments</th>
                        <th class="px-4 py-3 font-medium text-right">Payments</th>
                        <th class="px-4 py-3 font-medium text-right">Closing Payable</th>
                        <th class="px-4 py-3 font-medium text-right">Closing Credit</th>
                        <th class="px-4 py-3 font-medium text-center">Status</th>
                        <th class="px-4 py-3 font-medium text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-black">
                    @forelse($periods as $period)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-black">{{ $period->period_name }}</td>
                        <td class="px-4 py-3 text-black">
                            {{ $period->start_date->format('M d') }} - {{ $period->end_date->format('M d, Y') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            Rs. {{ number_format($period->opening_payable_balance, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            Rs. {{ number_format($period->output_tax - $period->input_tax - $period->import_tax, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            Rs. {{ number_format($period->adjustments, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right text-green-600">
                            Rs. {{ number_format($period->total_payments, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-black">
                            Rs. {{ number_format($period->closing_payable_balance, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-medium text-blue-600">
                            Rs. {{ number_format($period->closing_credit_balance, 2) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($period->period_status === 'Open')
                                <span class="px-2 py-1 rounded-full text-[10px] font-semibold bg-blue-100 text-blue-700 uppercase tracking-wider">Open</span>
                            @else
                                <span class="px-2 py-1 rounded-full text-[10px] font-semibold bg-slate-100 text-black uppercase tracking-wider">{{ $period->period_status }}</span>
                            @endif
                            <br>
                            @if($period->payment_status === 'Paid')
                                <span class="px-2 py-1 rounded-full text-[10px] font-semibold bg-green-100 text-green-700 uppercase tracking-wider mt-1 inline-block">Paid</span>
                            @elseif($period->payment_status === 'Partially Paid')
                                <span class="px-2 py-1 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-700 uppercase tracking-wider mt-1 inline-block">Part. Paid</span>
                            @else
                                <span class="px-2 py-1 rounded-full text-[10px] font-semibold bg-red-100 text-red-700 uppercase tracking-wider mt-1 inline-block">Unpaid</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('tax.periods.show', $period) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Details</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-8 text-center text-black">
                            No tax periods found. Click "New Tax Period" to start tracking.
                        </td>
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
