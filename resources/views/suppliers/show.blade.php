<x-layouts::app :title="__('Supplier Profile')">
    <!-- Top Navbar / Header -->
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('suppliers.index') }}" class="p-2 hover:bg-slate-100 rounded-lg transition" title="Back to Suppliers">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="text-lg font-bold text-slate-800">Supplier Profile & Ledger</h2>
        </div>
        <div></div>
    </div>

    <!-- Main Grid Content -->
    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto bg-slate-50">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            
            <!-- Left Info Panel Card -->
            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-xs p-6 space-y-6">
                <!-- Avatar and Title -->
                <div class="text-center space-y-2">
                    <div class="h-20 w-20 mx-auto rounded-full bg-blue-605 text-white font-extrabold flex items-center justify-center text-3xl shadow-md bg-blue-600">
                        {{ strtoupper(substr($supplier->name, 0, 1)) }}
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">{{ $supplier->name }}</h3>
                        <p class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">{{ $supplier->company_name }}</p>
                    </div>
                </div>

                <hr class="border-slate-100">

                <!-- Profile Field Info List -->
                <div class="space-y-4 text-xs">
                    <div>
                        <span class="text-slate-400 block font-semibold mb-0.5">Contact Person</span>
                        <span class="font-bold text-slate-700">{{ $supplier->name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-semibold mb-0.5">Phone Number</span>
                        <span class="font-bold text-slate-700">{{ $supplier->phone }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-semibold mb-0.5">Email Address</span>
                        <span class="font-bold text-slate-700">{{ $supplier->email ?: 'Not provided' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-semibold mb-0.5">Physical Address</span>
                        <span class="font-bold text-slate-700">{{ $supplier->address }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-semibold mb-0.5">Opening Balance</span>
                        <span class="font-bold text-slate-700">Rs. {{ number_format($supplier->opening_balance, 2) }}</span>
                    </div>
                    @if($supplier->bank_details)
                    <div>
                        <span class="text-slate-400 block font-semibold mb-0.5">Bank Account Details</span>
                        <p class="text-[11px] text-slate-600 font-mono whitespace-pre-line">{{ $supplier->bank_details }}</p>
                    </div>
                    @endif
                    <div>
                        <span class="text-slate-400 block font-semibold mb-0.5">Status</span>
                        @if($supplier->status)
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
                        <span class="text-slate-400 block font-semibold mb-0.5">Current Payable Balance</span>
                        @if($supplier->balance > 0)
                        <span class="text-base font-extrabold text-rose-600">Rs. {{ number_format($supplier->balance, 2) }}</span>
                        @else
                        <span class="text-base font-extrabold text-emerald-600">Rs. 0.00</span>
                        @endif
                    </div>
                    @if($supplier->notes)
                    <div>
                        <span class="text-slate-400 block font-semibold mb-0.5">Notes / Remainder</span>
                        <p class="text-[11px] text-slate-500 italic">{{ $supplier->notes }}</p>
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
                        onclick="switchTab('purchasesTab')" 
                        id="purchasesTabBtn" 
                        class="flex-1 py-2 rounded-lg text-xs font-bold transition cursor-pointer text-center text-slate-500 hover:text-slate-700"
                    >
                        Purchase Invoices History
                    </button>
                </div>

                <!-- Tab 1: Transactions History -->
                <div id="transactionsTab" class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-200/60">
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Transaction Ledger</h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200/60 text-slate-650 text-[10px] font-bold uppercase tracking-wider">
                                    <th class="px-6 py-4">Date</th>
                                    <th class="px-6 py-4">Reference No</th>
                                    <th class="px-6 py-4">Transaction Type</th>
                                    <th class="px-6 py-4 text-right">Debit (+ Payable)</th>
                                    <th class="px-6 py-4 text-right">Credit (- Payable)</th>
                                    <th class="px-6 py-4 text-right">Balance After</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700 text-xs">
                                @forelse($transactions as $tx)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="px-6 py-4 font-semibold text-slate-500">
                                        {{ $tx->transaction_date ? $tx->transaction_date->format('Y-m-d') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 font-mono text-[11px] text-slate-650">
                                        {{ $tx->reference_no ?: '-' }}
                                    </td>
                                    <td class="px-6 py-4 font-bold">
                                        <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold
                                            @if($tx->type === 'payment') bg-emerald-50 text-emerald-700
                                            @elseif($tx->type === 'opening_balance') bg-blue-50 text-blue-700
                                            @else bg-slate-100 text-slate-700 @endif
                                        ">
                                            {{ str_replace('_', ' ', $tx->type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium text-slate-700">
                                        {{ $tx->debit > 0 ? ('Rs. ' . number_format($tx->debit, 2)) : '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium text-emerald-600">
                                        {{ $tx->credit > 0 ? ('Rs. ' . number_format($tx->credit, 2)) : '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold text-slate-800">
                                        Rs. {{ number_format($tx->balance_after, 2) }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400">
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

                <!-- Tab 2: Purchases History -->
                <div id="purchasesTab" class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden hidden">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-200/60">
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Purchase Invoices History</h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200/60 text-slate-650 text-[10px] font-bold uppercase tracking-wider">
                                    <th class="px-6 py-4">PO Code / Inv</th>
                                    <th class="px-6 py-4">Date</th>
                                    <th class="px-6 py-4">Reference No</th>
                                    <th class="px-6 py-4 text-right">Subtotal</th>
                                    <th class="px-6 py-4 text-right">Grand Total</th>
                                    <th class="px-6 py-4 text-right">Due Balance</th>
                                    <th class="px-6 py-4">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700 text-xs">
                                @forelse($purchases as $purchase)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="px-6 py-4 font-mono font-bold text-blue-650">
                                        <a href="{{ route('purchases.show', $purchase->id) }}" class="hover:underline">
                                            {{ $purchase->purchase_no ?: $purchase->invoice_no }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-slate-500">
                                        {{ $purchase->purchase_date ? $purchase->purchase_date->format('Y-m-d') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 font-mono text-[11px]">
                                        {{ $purchase->reference_no ?: '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium text-slate-700">
                                        Rs. {{ number_format($purchase->subtotal, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium text-slate-700">
                                        Rs. {{ number_format($purchase->grand_total, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold text-rose-600">
                                        Rs. {{ number_format($purchase->due_amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold
                                            @if($purchase->status === 'Received') bg-emerald-100 text-emerald-800
                                            @elseif($purchase->status === 'Draft') bg-slate-100 text-slate-700
                                            @else bg-blue-100 text-blue-800 @endif
                                        ">
                                            {{ $purchase->status }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                        <span class="font-bold">No purchase invoices recorded for this supplier.</span>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($purchases->hasPages())
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200/60">
                        {{ $purchases->links() }}
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
            document.getElementById('purchasesTab').classList.add('hidden');
            
            // Remove active classes from buttons
            document.getElementById('transactionsTabBtn').className = "flex-1 py-2 rounded-lg text-xs font-bold transition cursor-pointer text-center text-slate-500 hover:text-slate-700";
            document.getElementById('purchasesTabBtn').className = "flex-1 py-2 rounded-lg text-xs font-bold transition cursor-pointer text-center text-slate-500 hover:text-slate-700";
            
            // Show active tab
            document.getElementById(tabId).classList.remove('hidden');
            
            // Set active class on active button
            const activeBtn = document.getElementById(tabId + 'Btn');
            activeBtn.className = "flex-1 py-2 rounded-lg text-xs font-bold transition cursor-pointer text-center bg-slate-800 text-white";
        }
    </script>
</x-layouts::app>
