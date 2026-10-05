<x-layouts::app title="Ledger Book">
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #printable-ledger, #printable-ledger * {
                visibility: visible;
            }
            #printable-ledger {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                background: white;
            }
            .print-hide {
                display: none !important;
            }
            .print-show {
                display: block !important;
            }
            .shadow-sm, .border-slate-200 {
                box-shadow: none !important;
                border-color: #000 !important;
            }
            @page { margin: 0.5cm; }
        }
    </style>

    <div id="printable-ledger" class="w-full">
        <!-- Print Only Header -->
        <div class="hidden print-show text-center mb-6 pb-4 border-b-2 border-slate-800">
            <h2 class="text-2xl font-bold text-black">Phone Shop POS - {{ \App\Support\Features::wholesaleEnabled() ? 'Retail & Wholesale' : 'Retail & Repair' }}</h2>
            <h3 class="text-xl font-semibold text-black mt-1">Ledger Book Report</h3>
            <p class="text-sm text-black mt-2">Date Printed: {{ \Carbon\Carbon::now()->format('M d, Y h:i A') }}</p>
        </div>

        <!-- Normal Header -->
        <div class="flex items-center justify-between mb-6 print-hide">
            <div>
                <h1 class="text-2xl font-bold text-black">Ledger Book</h1>
                <p class="text-sm text-black">View detailed transaction history for any account</p>
            </div>
            <div>
                <a href="{{ request()->fullUrlWithQuery(['type' => 'pdf']) }}" class="bg-white border border-slate-300 hover:bg-slate-50 text-black px-4 py-2 rounded-lg font-medium text-sm flex items-center gap-2 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Export PDF
                </a>
            </div>
        </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6 print-hide">
        <form action="{{ route('accounting.ledger.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-bold text-black mb-1">Select Account *</label>
                <select name="account_id" required class="w-full rounded-lg border-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" onchange="this.form.submit()">
                    <option value="">-- Choose Account --</option>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>
                            {{ $acc->account_name }} ({{ $acc->account_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-black mb-1">Date Range</label>
                <select name="date_filter" class="rounded-lg border-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" onchange="this.form.submit()">
                    <option value="today" {{ request('date_filter', 'today') == 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ request('date_filter') == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="this_week" {{ request('date_filter') == 'this_week' ? 'selected' : '' }}>This Week</option>
                    <option value="last_week" {{ request('date_filter') == 'last_week' ? 'selected' : '' }}>Last Week</option>
                    <option value="this_month" {{ request('date_filter') == 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="last_month" {{ request('date_filter') == 'last_month' ? 'selected' : '' }}>Last Month</option>
                </select>
            </div>
            <div>
                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg font-medium text-sm transition-colors shadow-sm">View Ledger</button>
            </div>
        </form>
    </div>

    @if($selectedAccount)
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                <h4 class="text-sm font-medium text-black mb-1">Opening Balance</h4>
                <div class="text-2xl font-bold {{ $openingBalance < 0 ? 'text-rose-600' : 'text-black' }}">Rs. {{ number_format($openingBalance, 2) }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                <h4 class="text-sm font-medium text-black mb-1">Total Debit</h4>
                <div class="text-2xl font-bold text-black">Rs. {{ number_format($totalDebit, 2) }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                <h4 class="text-sm font-medium text-black mb-1">Total Credit</h4>
                <div class="text-2xl font-bold text-black">Rs. {{ number_format($totalCredit, 2) }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 border-l-4 border-l-blue-500">
                <h4 class="text-sm font-medium text-blue-600 mb-1">Closing Balance</h4>
                <div class="text-2xl font-bold text-black">Rs. {{ number_format($closingBalance, 2) }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50 text-center">
                <h2 class="text-xl font-bold text-black">{{ $selectedAccount->account_name }}</h2>
                <p class="text-sm text-black">Account Code: {{ $selectedAccount->account_code }} | Type: {{ $selectedAccount->account_type }}</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white border-b border-slate-200">
                            <th class="p-4 text-xs font-bold text-black uppercase tracking-wider">Date</th>
                            <th class="p-4 text-xs font-bold text-black uppercase tracking-wider">Journal No</th>
                            <th class="p-4 text-xs font-bold text-black uppercase tracking-wider">Reference</th>
                            <th class="p-4 text-xs font-bold text-black uppercase tracking-wider">Description</th>
                            <th class="p-4 text-xs font-bold text-black uppercase tracking-wider text-right">Debit (Rs)</th>
                            <th class="p-4 text-xs font-bold text-black uppercase tracking-wider text-right">Credit (Rs)</th>
                            <th class="p-4 text-xs font-bold text-black uppercase tracking-wider text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <!-- Opening Balance Row -->
                        <tr class="bg-slate-50/50">
                            <td class="p-4 text-sm font-medium text-black" colspan="4">Opening Balance</td>
                            <td class="p-4 text-right"></td>
                            <td class="p-4 text-right"></td>
                            <td class="p-4 text-sm font-bold text-black text-right">{{ number_format($openingBalance, 2) }}</td>
                        </tr>

                        @php $runningBalance = $openingBalance; @endphp
                        @foreach($ledgerEntries as $entry)
                            @php
                                if (in_array($selectedAccount->account_type, ['Asset', 'Expense'])) {
                                    $runningBalance = $runningBalance + $entry->debit - $entry->credit;
                                } else {
                                    $runningBalance = $runningBalance + $entry->credit - $entry->debit;
                                }
                            @endphp
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="p-4 text-sm text-black">{{ \Carbon\Carbon::parse($entry->date)->format('M d, Y') }}</td>
                                <td class="p-4 text-sm font-medium text-black">{{ $entry->journal_no }}</td>
                                <td class="p-4 text-sm text-black">{{ $entry->reference_no ?? '-' }}</td>
                                <td class="p-4 text-sm text-black">
                                    {{ \App\Support\Features::retailText($entry->note ?? '') }}
                                    @if($entry->notes)
                                        <br><span class="text-xs text-black">{{ \App\Support\Features::retailText($entry->notes ?? '') }}</span>
                                    @endif
                                </td>
                                <td class="p-4 text-sm text-black text-right">{{ $entry->debit > 0 ? number_format($entry->debit, 2) : '-' }}</td>
                                <td class="p-4 text-sm text-black text-right">{{ $entry->credit > 0 ? number_format($entry->credit, 2) : '-' }}</td>
                                <td class="p-4 text-sm font-medium text-black text-right">{{ number_format($runningBalance, 2) }}</td>
                            </tr>
                        @endforeach

                        <!-- Closing Balance Row -->
                        <tr class="bg-blue-50/30 border-t-2 border-slate-200">
                            <td class="p-4 text-sm font-bold text-black" colspan="4">Closing Balance</td>
                            <td class="p-4 text-right font-bold text-black">{{ number_format($totalDebit, 2) }}</td>
                            <td class="p-4 text-right font-bold text-black">{{ number_format($totalCredit, 2) }}</td>
                            <td class="p-4 text-sm font-bold text-blue-700 text-right">{{ number_format($closingBalance, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center print-hide">
            <svg class="w-16 h-16 text-black mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
            <h3 class="text-lg font-bold text-black mb-1">Select an Account</h3>
            <p class="text-black">Please select an account from the dropdown above to view its ledger.</p>
        </div>
    @endif
    </div>
</x-layouts::app>