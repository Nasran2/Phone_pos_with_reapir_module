<x-layouts::app :title="__('Customer Profile')">
    <!-- Top Navbar / Header -->
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('customers.index') }}" class="p-2 hover:bg-slate-100 rounded-lg transition" title="Back to Customers">
                <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="text-lg font-bold text-black">Customer Profile & Ledger</h2>
        </div>
        <div class="flex items-center gap-2">
            <a 
                href="https://wa.me/{{ '94' . ltrim(preg_replace('/[^0-9]/', '', $customer->phone), '0') }}" 
                target="_blank"
                class="flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition shadow-sm"
            >
                <svg class="w-4 h-4 fill-currentColor" viewBox="0 0 24 24">
                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.717-1.458L0 24zm6.59-4.846c1.6.95 3.488 1.459 5.419 1.46h.007c5.632 0 10.21-4.58 10.21-10.21 0-2.724-1.06-5.285-2.984-7.21C17.38 1.268 14.825.21 12.1.21h-.007C6.46.21 1.882 4.79 1.882 10.42c-.001 1.93.502 3.81 1.458 5.405L2.342 20.6l4.907-1.287zM17.472 14.382c-.3-.149-1.777-.878-2.046-.977-.27-.1-.466-.149-.663.149-.197.3-.765.977-.938 1.176-.173.2-.347.223-.647.074-.3-.149-1.266-.467-2.41-1.488-.89-.794-1.49-1.775-1.665-2.074-.173-.3-.018-.462.13-.61.135-.133.3-.347.45-.52.15-.173.2-.3.3-.5.1-.2.05-.375-.025-.524-.075-.15-.663-1.597-.91-2.193-.24-.58-.48-.5-.66-.51-.17-.01-.37-.01-.57-.01-.2 0-.52.075-.8.375-.27.3-1.05 1.03-1.05 2.5 0 1.48 1.07 2.9 1.22 3.1.15.2 2.11 3.22 5.11 4.52.71.31 1.27.49 1.7.63.72.23 1.37.2 1.89.12.58-.09 1.78-.73 2.03-1.43.25-.7.25-1.29.17-1.42-.08-.13-.27-.2-.57-.35z"/>
                </svg>
                <span>WhatsApp</span>
            </a>
        </div>
    </div>

    @if($repairJobs !== null)
        <section class="m-6 min-w-0 rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="font-bold mb-3">Repair history</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left" style="min-width:680px">
                    <thead class="text-slate-500 border-b border-slate-200"><tr>
                        @foreach(['Job No', 'Date', 'Device', 'Problems', 'Status', 'Total', 'Balance'] as $heading)
                            <th class="py-3 pr-4 font-semibold">{{ $heading }}</th>
                        @endforeach
                    </tr></thead>
                    <tbody>
                        @forelse($repairJobs as $repairJob)
                            <tr class="border-b border-slate-100">
                                <td class="py-3 pr-4"><a class="text-blue-600 font-semibold" href="{{ route('repairs.show', $repairJob) }}">{{ $repairJob->job_no }}</a></td>
                                <td class="py-3 pr-4 whitespace-nowrap">{{ $repairJob->created_at->format('M j, Y') }}</td>
                                <td class="py-3 pr-4">{{ $repairJob->device?->brand }} {{ $repairJob->device?->model }}</td>
                                <td class="py-3 pr-4">{{ implode(', ', $repairJob->problem_snapshot ?? []) }}</td>
                                <td class="py-3 pr-4">{{ $repairJob->status_label }}</td>
                                <td class="py-3 pr-4 whitespace-nowrap">{{ \App\Support\SystemSettings::money((float) $repairJob->total_amount) }}</td>
                                <td class="py-3 whitespace-nowrap">{{ \App\Support\SystemSettings::money((float) $repairJob->balance_amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-4 text-slate-500">No repair visits recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $repairJobs->links() }}
        </section>
    @endif
    <!-- Main Grid Content -->
    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto bg-slate-50">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            
            <!-- Left Info Panel Card -->
            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-xs p-6 space-y-6">
                <!-- Avatar and Title -->
                <div class="text-center space-y-2">
                    <div class="h-20 w-20 mx-auto rounded-full bg-blue-600 text-white font-extrabold flex items-center justify-center text-3xl shadow-md">
                        {{ strtoupper(substr($customer->name, 0, 1)) }}
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-black">{{ $customer->name }}</h3>
                        <p class="text-[11px] text-black font-semibold uppercase tracking-wider">Customer Profile</p>
                    </div>
                </div>

                <hr class="border-slate-100">

                <!-- Profile Field Info List -->
                <div class="space-y-4 text-xs">
                    <div>
                        <span class="text-black block font-semibold mb-0.5">Phone Number</span>
                        <span class="font-bold text-black">{{ $customer->phone }}</span>
                    </div>
                    <div>
                        <span class="text-black block font-semibold mb-0.5">Email Address</span>
                        <span class="font-bold text-black">{{ $customer->email ?: 'Not provided' }}</span>
                    </div>
                    <div>
                        <span class="text-black block font-semibold mb-0.5">Physical Address</span>
                        <span class="font-bold text-black">{{ $customer->address ?: 'Not provided' }}</span>
                    </div>
                    <div>
                        <span class="text-black block font-semibold mb-0.5">Opening Balance</span>
                        <span class="font-bold text-black">Rs. {{ number_format($customer->opening_balance, 2) }}</span>
                    </div>
                    <div>
                        <span class="text-black block font-semibold mb-0.5">Credit Limit</span>
                        <span class="font-bold text-black">{{ $customer->credit_limit ? ('Rs. ' . number_format($customer->credit_limit, 2)) : 'Unlimited' }}</span>
                    </div>
                    <div>
                        <span class="text-black block font-semibold mb-0.5">Status</span>
                        @if($customer->status)
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                            Active
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800">
                            Inactive
                        </span>
                        @endif
                    </div>
                    <div>
                        <span class="text-black block font-semibold mb-0.5">Current Due Balance</span>
                        @if($customer->balance > 0)
                        <span class="text-base font-extrabold text-rose-600">Rs. {{ number_format($customer->balance, 2) }}</span>
                        @else
                        <span class="text-base font-extrabold text-emerald-600">Rs. 0.00</span>
                        @endif
                    </div>
                    <div>
                        <span class="text-black block font-semibold mb-0.5">Cheque Hold Balance</span>
                        @if($holdAmount > 0)
                        <span class="text-base font-extrabold text-amber-500">Rs. {{ number_format($holdAmount, 2) }}</span>
                        @else
                        <span class="text-base font-extrabold text-black">Rs. 0.00</span>
                        @endif
                    </div>
                    @if($customer->notes)
                    <div>
                        <span class="text-black block font-semibold mb-0.5">Notes / Remark</span>
                        <p class="text-[11px] text-black italic">{{ $customer->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Right Tabs & Ledger Tables Panel -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Navigation Tabs -->
                <div class="bg-white p-1.5 rounded-xl border border-slate-200/60 shadow-xs flex items-center gap-2">
                    <button 
                        onclick="switchTab('transactionsTab')" 
                        id="transactionsTabBtn" 
                        class="flex-1 py-2 rounded-lg text-xs font-bold transition cursor-pointer text-center bg-slate-800 text-white"
                    >
                        Transactions History Ledger
                    </button>
                    <button 
                        onclick="switchTab('salesTab')" 
                        id="salesTabBtn" 
                        class="flex-1 py-2 rounded-lg text-xs font-bold transition cursor-pointer text-center text-black hover:text-black"
                    >
                        Sales Invoices History
                    </button>
                </div>

                <!-- Tab 1: Transactions History -->
                <div id="transactionsTab" class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-200/60 flex items-center justify-between">
                        <h4 class="text-xs font-bold text-black uppercase tracking-wider">Transaction Ledger</h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200/60 text-black text-[10px] font-bold uppercase tracking-wider">
                                    <th class="px-6 py-4">Date</th>
                                    <th class="px-6 py-4">Reference No</th>
                                    <th class="px-6 py-4">Transaction Type</th>
                                    <th class="px-6 py-4 text-right">Debit (+ Due)</th>
                                    <th class="px-6 py-4 text-right">Credit (- Due)</th>
                                    <th class="px-6 py-4 text-right">Balance After</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-black text-xs">
                                @forelse($transactions as $tx)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="px-6 py-4 font-semibold text-black">
                                        {{ $tx->transaction_date ? $tx->transaction_date->format('Y-m-d') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 font-mono text-[11px] text-black">
                                        {{ $tx->reference_no ?: '-' }}
                                    </td>
                                    <td class="px-6 py-4 font-bold">
                                        <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold
                                            @if($tx->type === 'payment') bg-emerald-50 text-emerald-700
                                            @elseif($tx->type === 'opening_balance') bg-blue-50 text-blue-700
                                            @else bg-slate-100 text-black @endif
                                        ">
                                            {{ str_replace('_', ' ', $tx->type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium text-black">
                                        {{ $tx->debit > 0 ? ('Rs. ' . number_format($tx->debit, 2)) : '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium text-emerald-600">
                                        {{ $tx->credit > 0 ? ('Rs. ' . number_format($tx->credit, 2)) : '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold text-black">
                                        Rs. {{ number_format($tx->balance_after, 2) }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-black">
                                        <span class="font-bold">No ledger transactions registered yet.</span>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($transactions->hasPages())
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200/60">
                        {{ $transactions->links() }}
                    </div>
                    @endif
                </div>

                <!-- Tab 2: Sales History -->
                <div id="salesTab" class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden hidden">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-200/60">
                        <h4 class="text-xs font-bold text-black uppercase tracking-wider">Sales Invoices History</h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200/60 text-black text-[10px] font-bold uppercase tracking-wider">
                                    <th class="px-6 py-4">Invoice No</th>
                                    <th class="px-6 py-4">Date</th>
                                    <th class="px-6 py-4">Billing Type</th>
                                    <th class="px-6 py-4 text-right">Invoice Total</th>
                                    <th class="px-6 py-4 text-right">Paid Amount</th>
                                    <th class="px-6 py-4 text-right">Due Balance</th>
                                    <th class="px-6 py-4">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-black text-xs">
                                @forelse($sales as $sale)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="px-6 py-4 font-mono font-bold text-blue-650">
                                        <a href="{{ route('sales.show', $sale->id) }}" class="hover:underline">
                                            {{ $sale->invoice_no }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-black">
                                        {{ $sale->created_at ? $sale->created_at->format('Y-m-d') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 font-semibold uppercase text-[10px] text-black">
                                        {{ \App\Support\Features::wholesaleEnabled() ? $sale->pos_type : 'Sale' }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium text-black">
                                        Rs. {{ number_format($sale->payable_amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium text-emerald-600">
                                        Rs. {{ number_format($sale->paid_amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold text-rose-600">
                                        Rs. {{ number_format($sale->due_amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($sale->payment_status === 'Paid')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">Paid</span>
                                        @elseif($sale->payment_status === 'Partial')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800">Partial</span>
                                        @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">Due</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-black">
                                        <span class="font-bold">No sales invoices recorded for this customer.</span>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($sales->hasPages())
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200/60">
                        {{ $sales->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Scripting for tab toggles -->
    <script>
        function switchTab(tabId) {
            // Hide all tabs
            document.getElementById('transactionsTab').classList.add('hidden');
            document.getElementById('salesTab').classList.add('hidden');
            
            // Remove active classes from buttons
            document.getElementById('transactionsTabBtn').className = "flex-1 py-2 rounded-lg text-xs font-bold transition cursor-pointer text-center text-black hover:text-black";
            document.getElementById('salesTabBtn').className = "flex-1 py-2 rounded-lg text-xs font-bold transition cursor-pointer text-center text-black hover:text-black";
            
            // Show active tab
            document.getElementById(tabId).classList.remove('hidden');
            
            // Set active class on active button
            const activeBtn = document.getElementById(tabId + 'Btn');
            activeBtn.className = "flex-1 py-2 rounded-lg text-xs font-bold transition cursor-pointer text-center bg-slate-800 text-white";
        }
    </script>
</x-layouts::app>
