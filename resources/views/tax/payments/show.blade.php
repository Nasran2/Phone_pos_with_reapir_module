<x-layouts::app title="Payment Details">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Payment: {{ $payment->payment_number }}</h1>
            <p class="text-sm text-slate-500">Record of tax payment made on {{ $payment->payment_date->format('M d, Y') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('tax.payments.index') }}" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 text-sm font-medium transition-colors">
                Back to List
            </a>
        </div>
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

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Details Card -->
        <div class="md:col-span-2 space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-4 flex justify-between items-center">
                    <h2 class="font-semibold text-slate-800">Payment Information</h2>
                    @if($payment->status === 'Approved')
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700 uppercase tracking-wider">Approved</span>
                    @elseif($payment->status === 'Draft')
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 uppercase tracking-wider">Draft</span>
                    @elseif($payment->status === 'Reversed')
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700 uppercase tracking-wider">Reversed</span>
                    @endif
                </div>
                
                <div class="p-6 grid grid-cols-2 gap-y-6 gap-x-8">
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wider mb-1 font-semibold">Total Amount</p>
                        <p class="text-2xl font-bold text-slate-900">Rs. {{ number_format($payment->total_amount, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wider mb-1 font-semibold">Method</p>
                        <p class="text-base text-slate-900">{{ $payment->payment_method }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wider mb-1 font-semibold">Bank / Ref No.</p>
                        <p class="text-base text-slate-900">{{ $payment->bank_reference ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wider mb-1 font-semibold">Internal Notes</p>
                        <p class="text-sm text-slate-700">{{ $payment->notes ?: '-' }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Allocations -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h2 class="font-semibold text-slate-800">Payment Allocation</h2>
                </div>
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">Tax Period</th>
                            <th class="px-6 py-3 font-medium">Type</th>
                            <th class="px-6 py-3 font-medium text-right">Allocated Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($payment->allocations as $allocation)
                        <tr>
                            <td class="px-6 py-3 font-medium text-slate-900">{{ $allocation->period->period_name }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $allocation->allocation_type }}</td>
                            <td class="px-6 py-3 text-right font-medium text-blue-600">Rs. {{ number_format($allocation->allocated_amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Actions & History -->
        <div class="space-y-6">
            @if($payment->status === 'Draft' && auth()->user()->hasPermission('tax_payments.approve'))
            <div class="bg-white rounded-xl border border-amber-200 shadow-sm overflow-hidden p-5">
                <h3 class="font-semibold text-slate-800 mb-2">Pending Approval</h3>
                <p class="text-sm text-slate-500 mb-4">This payment is in draft mode and will not affect tax balances until approved.</p>
                <form action="{{ route('tax.payments.approve', $payment) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition">
                        Approve Payment
                    </button>
                </form>
            </div>
            @endif
            
            @if($payment->status === 'Approved' && auth()->user()->hasPermission('tax_payments.reverse'))
            <div class="bg-white rounded-xl border border-red-200 shadow-sm overflow-hidden p-5">
                <h3 class="font-semibold text-slate-800 mb-2 text-red-600">Reverse Payment</h3>
                <p class="text-sm text-slate-500 mb-4">Reversing will subtract this amount from the allocated tax periods and reopen their balances.</p>
                <form action="{{ route('tax.payments.reverse', $payment) }}" method="POST" onsubmit="return confirm('Are you sure you want to reverse this payment?');">
                    @csrf
                    <div class="mb-3">
                        <input type="text" name="reversal_reason" placeholder="Reason for reversal..." required class="w-full text-sm border-slate-300 rounded-lg focus:ring-red-500 focus:border-red-500">
                    </div>
                    <button type="submit" class="w-full py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition">
                        Reverse Payment
                    </button>
                </form>
            </div>
            @endif

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-4 bg-slate-50">
                    <h2 class="font-semibold text-slate-800 text-sm">Audit History</h2>
                </div>
                <div class="p-5 space-y-4">
                    <div class="flex gap-3">
                        <div class="flex-shrink-0 mt-1"><div class="h-2 w-2 rounded-full bg-slate-400"></div></div>
                        <div>
                            <p class="text-sm font-medium text-slate-900">Entered by {{ $payment->enterer?->name ?? 'System' }}</p>
                            <p class="text-xs text-slate-500">{{ $payment->created_at->format('M d, Y h:i A') }}</p>
                        </div>
                    </div>
                    
                    @if($payment->approved_at)
                    <div class="flex gap-3">
                        <div class="flex-shrink-0 mt-1"><div class="h-2 w-2 rounded-full bg-green-500"></div></div>
                        <div>
                            <p class="text-sm font-medium text-slate-900">Approved by {{ $payment->approver?->name ?? 'System' }}</p>
                            <p class="text-xs text-slate-500">{{ $payment->approved_at->format('M d, Y h:i A') }}</p>
                        </div>
                    </div>
                    @endif
                    
                    @if($payment->reversed_at)
                    <div class="flex gap-3">
                        <div class="flex-shrink-0 mt-1"><div class="h-2 w-2 rounded-full bg-red-500"></div></div>
                        <div>
                            <p class="text-sm font-medium text-slate-900">Reversed</p>
                            <p class="text-xs text-slate-500 mb-1">{{ $payment->reversed_at->format('M d, Y h:i A') }}</p>
                            <p class="text-xs text-slate-700 italic border-l-2 border-slate-300 pl-2">"{{ $payment->reversal_reason }}"</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts::app>
