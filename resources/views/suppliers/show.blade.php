<x-layouts::app :title="$supplier->name">
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div>
            <h2 class="text-lg font-bold text-slate-850">{{ $supplier->name }}</h2>
            <p class="text-xs text-slate-500">{{ $supplier->company_name ?: 'Supplier' }} {{ $supplier->phone ? '&bull; '.$supplier->phone : '' }}</p>
        </div>
        <a href="{{ route('suppliers.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold">Back</a>
    </div>

    <div class="p-6 md:p-8 space-y-6 flex-1 bg-slate-50">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-slate-200/70 p-4 shadow-xs">
                <p class="text-[11px] font-bold uppercase text-slate-400">Current Balance</p>
                <p class="mt-2 text-lg font-black {{ (float) $supplier->balance > 0 ? 'text-red-600' : 'text-emerald-700' }}">Rs. {{ number_format((float) $supplier->balance, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200/70 p-4 shadow-xs">
                <p class="text-[11px] font-bold uppercase text-slate-400">Total Purchases</p>
                <p class="mt-2 text-lg font-black text-slate-850">Rs. {{ number_format((float) $supplier->purchases->sum(fn($p) => (float) ($p->grand_total ?: $p->total_amount)), 2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200/70 p-4 shadow-xs">
                <p class="text-[11px] font-bold uppercase text-slate-400">Paid</p>
                <p class="mt-2 text-lg font-black text-emerald-700">Rs. {{ number_format((float) $supplier->purchases->sum('paid_amount'), 2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200/70 p-4 shadow-xs">
                <p class="text-[11px] font-bold uppercase text-slate-400">Due</p>
                <p class="mt-2 text-lg font-black text-red-600">Rs. {{ number_format((float) $supplier->purchases->sum('due_amount'), 2) }}</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/70 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100"><h3 class="font-black text-slate-850">Complete Ledger</h3></div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-600">
                        <tr><th class="px-5 py-4">Date</th><th class="px-5 py-4">Type</th><th class="px-5 py-4">Reference</th><th class="px-5 py-4">Description</th><th class="px-5 py-4 text-right">Debit</th><th class="px-5 py-4 text-right">Credit</th><th class="px-5 py-4 text-right">Balance</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @forelse($ledger as $row)
                        <tr>
                            <td class="px-5 py-4">{{ $row->date ? \Carbon\Carbon::parse($row->date)->format('Y-m-d h:i A') : '-' }}</td>
                            <td class="px-5 py-4 font-bold">{{ $row->type }}</td>
                            <td class="px-5 py-4">{{ $row->reference_no ?: '-' }}</td>
                            <td class="px-5 py-4">{{ $row->description }}</td>
                            <td class="px-5 py-4 text-right">Rs. {{ number_format((float) $row->debit, 2) }}</td>
                            <td class="px-5 py-4 text-right">Rs. {{ number_format((float) $row->credit, 2) }}</td>
                            <td class="px-5 py-4 text-right font-black">Rs. {{ number_format((float) $row->balance, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400 font-semibold">No ledger records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl border border-slate-200/70 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100"><h3 class="font-black text-slate-850">Complete Purchase History</h3></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-600">
                            <tr><th class="px-5 py-4">Purchase</th><th class="px-5 py-4">Date</th><th class="px-5 py-4">Status</th><th class="px-5 py-4 text-right">Total</th><th class="px-5 py-4 text-right">Due</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @forelse($supplier->purchases->sortByDesc('created_at') as $purchase)
                            <tr>
                                <td class="px-5 py-4 font-bold"><a href="{{ route('purchases.show', $purchase) }}" class="text-blue-600 hover:underline">{{ method_exists($purchase, 'displayNo') ? $purchase->displayNo() : $purchase->invoice_no }}</a></td>
                                <td class="px-5 py-4">{{ $purchase->created_at->format('Y-m-d h:i A') }}</td>
                                <td class="px-5 py-4">{{ $purchase->status }}</td>
                                <td class="px-5 py-4 text-right">Rs. {{ number_format((float) ($purchase->grand_total ?: $purchase->total_amount), 2) }}</td>
                                <td class="px-5 py-4 text-right font-bold text-red-600">Rs. {{ number_format((float) $purchase->due_amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400 font-semibold">No purchases found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/70 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100"><h3 class="font-black text-slate-850">Payment History</h3></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-600">
                            <tr><th class="px-5 py-4">Date</th><th class="px-5 py-4">Reference</th><th class="px-5 py-4">Method</th><th class="px-5 py-4 text-right">Amount</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @forelse($paymentHistory as $payment)
                            <tr>
                                <td class="px-5 py-4">{{ $payment->date ? \Carbon\Carbon::parse($payment->date)->format('Y-m-d') : '-' }}</td>
                                <td class="px-5 py-4">{{ $payment->reference_no ?: '-' }}</td>
                                <td class="px-5 py-4 font-bold">{{ $payment->method }}</td>
                                <td class="px-5 py-4 text-right font-black text-emerald-700">Rs. {{ number_format((float) $payment->amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-slate-400 font-semibold">No payments found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-layouts::app>
