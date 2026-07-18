<x-layouts::app title="Payment Register">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Payment Register</h1>
            <p class="text-sm text-slate-500">Log of all tax payments made to the authorities.</p>
        </div>
        <a href="{{ route('tax.reports.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Back to Reports</a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Payment No.</th>
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium">Method</th>
                        <th class="px-4 py-3 font-medium">Allocated Month</th>
                        <th class="px-4 py-3 font-medium text-right">Amount</th>
                        <th class="px-4 py-3 font-medium text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($payments as $payment)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $payment->payment_number }}</td>
                        <td class="px-4 py-3">{{ \Carbon\Carbon::parse($payment->payment_date)->format('M d, Y') }}</td>
                        <td class="px-4 py-3">
                            {{ $payment->payment_method }}
                            @if($payment->reference_number)
                                <span class="text-xs text-slate-400 ml-1">({{ $payment->reference_number }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @foreach($payment->allocations as $alloc)
                                <span class="inline-block px-2 py-0.5 bg-blue-50 text-blue-700 rounded text-xs">{{ $alloc->period->period_name }}</span>
                            @endforeach
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-slate-900">Rs. {{ number_format($payment->total_amount, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($payment->status === 'Approved')
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold uppercase">Approved</span>
                            @elseif($payment->status === 'Draft')
                                <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-semibold uppercase">Draft</span>
                            @elseif($payment->status === 'Reversed')
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold uppercase">Reversed</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">No payment data available.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200">
            {{ $payments->links() }}
        </div>
    </div>
</x-layouts::app>
