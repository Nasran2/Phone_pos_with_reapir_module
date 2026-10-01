<x-layouts::app title="Tax Payments">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black">Tax Payments</h1>
            <p class="text-sm text-black">Manage, record, and approve payments made to tax authorities.</p>
        </div>
        
        @if(auth()->user()->hasPermission('tax_payments.create'))
        <a href="{{ route('tax.payments.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium transition-colors">
            + Record Payment
        </a>
        @endif
    </div>

    <!-- Alert -->
    @if(session('success'))
    <div class="mb-4 bg-green-50 text-green-800 p-4 rounded-lg text-sm border border-green-200">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-4 bg-red-50 text-red-800 p-4 rounded-lg text-sm border border-red-200">
        {{ session('error') }}
    </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 border-b border-slate-200 text-black">
                    <tr>
                        <th class="px-4 py-3 font-medium">Payment Date</th>
                        <th class="px-4 py-3 font-medium">Payment No.</th>
                        <th class="px-4 py-3 font-medium">Method</th>
                        <th class="px-4 py-3 font-medium text-right">Amount</th>
                        <th class="px-4 py-3 font-medium text-center">Status</th>
                        <th class="px-4 py-3 font-medium text-center">Entered By</th>
                        <th class="px-4 py-3 font-medium text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-black">
                    @forelse($payments as $payment)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 text-black">{{ $payment->payment_date->format('M d, Y') }}</td>
                        <td class="px-4 py-3 font-medium text-blue-600">
                            {{ $payment->payment_number }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-black">{{ $payment->payment_method }}</span>
                            @if($payment->bank_reference)
                                <span class="text-xs text-black block">{{ $payment->bank_reference }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-black">
                            Rs. {{ number_format($payment->total_amount, 2) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($payment->status === 'Approved')
                                <span class="px-2 py-1 rounded-full text-[10px] font-semibold bg-green-100 text-green-700 uppercase tracking-wider">Approved</span>
                            @elseif($payment->status === 'Draft')
                                <span class="px-2 py-1 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-700 uppercase tracking-wider">Draft</span>
                            @elseif($payment->status === 'Reversed')
                                <span class="px-2 py-1 rounded-full text-[10px] font-semibold bg-red-100 text-red-700 uppercase tracking-wider">Reversed</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-black">
                            {{ $payment->enterer?->name ?? 'System' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('tax.payments.show', $payment) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium mr-2">Details</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-black">
                            No tax payments found.
                        </td>
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
