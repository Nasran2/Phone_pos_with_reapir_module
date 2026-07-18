<x-layouts::app title="Journal Entries">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Journal Entries</h1>
            <p class="text-sm text-slate-500">View and create manual double-entry journals</p>
        </div>
        <button onclick="document.getElementById('add-journal-modal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium text-sm flex items-center gap-2 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add Manual Entry
        </button>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-emerald-50 text-emerald-700 p-4 rounded-lg border border-emerald-100 font-medium">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-rose-50 text-rose-700 p-4 rounded-lg border border-rose-100 font-medium">
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-4 bg-rose-50 text-rose-700 p-4 rounded-lg border border-rose-100 font-medium">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
        <form action="{{ route('accounting.journal-entries.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Date Range</label>
                <select name="date_filter" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" onchange="this.form.submit()">
                    <option value="today" {{ request('date_filter', 'today') == 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ request('date_filter') == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="this_week" {{ request('date_filter') == 'this_week' ? 'selected' : '' }}>This Week</option>
                    <option value="last_week" {{ request('date_filter') == 'last_week' ? 'selected' : '' }}>Last Week</option>
                    <option value="this_month" {{ request('date_filter') == 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="last_month" {{ request('date_filter') == 'last_month' ? 'selected' : '' }}>Last Month</option>
                </select>
            </div>
            <div>
                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg font-medium text-sm transition-colors shadow-sm">Filter</button>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Date & Ref</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Type & Description</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Entries (Account, Debit, Credit)</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($journals as $journal)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-4 align-top">
                                <div class="text-sm font-medium text-slate-900">{{ $journal->journal_date->format('M d, Y') }}</div>
                                <div class="text-xs text-slate-500 mt-1">{{ $journal->journal_no }}</div>
                                @if($journal->reference_no)
                                    <div class="text-xs text-slate-400 mt-1">Ref: {{ $journal->reference_no }}</div>
                                @endif
                            </td>
                            <td class="p-4 align-top">
                                <div class="text-sm">
                                    @if($journal->is_auto_generated)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 mb-1">Auto: {{ $journal->reference_type }}</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800 mb-1">Manual Entry</span>
                                    @endif
                                </div>
                                <div class="text-sm text-slate-600 mt-1">{{ $journal->description }}</div>
                            </td>
                            <td class="p-4">
                                <div class="bg-slate-50 rounded border border-slate-100 p-2 text-sm">
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr class="text-slate-500 border-b border-slate-200">
                                                <th class="pb-1 font-medium">Account</th>
                                                <th class="pb-1 font-medium text-right">Debit (Rs.)</th>
                                                <th class="pb-1 font-medium text-right">Credit (Rs.)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach($journal->items as $item)
                                                <tr>
                                                    <td class="py-1 text-slate-700">{{ $item->account->account_name }}</td>
                                                    <td class="py-1 text-right text-slate-900 font-medium">{{ $item->debit > 0 ? number_format($item->debit, 2) : '-' }}</td>
                                                    <td class="py-1 text-right text-slate-900 font-medium">{{ $item->credit > 0 ? number_format($item->credit, 2) : '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                            <td class="p-4 text-center align-top">
                                @if(!$journal->is_auto_generated)
                                    <form action="{{ route('accounting.journal-entries.destroy', $journal) }}" method="POST" class="inline" onsubmit="return confirmAction(event, 'Are you sure you want to delete this manual journal entry? Account balances will be reversed.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-rose-500 hover:text-rose-700 transition">
                                            <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400 italic" title="Auto-generated entries cannot be deleted here. Delete the source transaction instead.">Locked</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-8 text-center text-slate-500">No journal entries found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($journals->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $journals->links() }}
            </div>
        @endif
    </div>

    <!-- Add Manual Journal Modal -->
    <div id="add-journal-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl overflow-hidden my-auto">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800 text-lg">Create Manual Journal Entry</h3>
                <button onclick="document.getElementById('add-journal-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form action="{{ route('accounting.journal-entries.store') }}" method="POST">
                @csrf
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Journal Date *</label>
                            <input type="date" name="journal_date" value="{{ date('Y-m-d') }}" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Reference No (Optional)</label>
                            <input type="text" name="reference_no" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-sm font-bold text-slate-700 mb-1">Description</label>
                            <input type="text" name="description" placeholder="Explanation for this manual entry..." class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                        <table class="w-full text-left" id="journal-items-table">
                            <thead>
                                <tr class="text-xs font-bold text-slate-600 uppercase">
                                    <th class="pb-2 w-1/2">Account</th>
                                    <th class="pb-2">Debit</th>
                                    <th class="pb-2">Credit</th>
                                    <th class="pb-2 w-10"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Line 1 -->
                                <tr class="item-row">
                                    <td class="pr-2 pb-2">
                                        <select name="items[0][account_id]" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                            <option value="">Select Account</option>
                                            @foreach($accounts as $acc)
                                                <option value="{{ $acc->id }}">{{ $acc->account_name }} ({{ $acc->account_type }})</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="pr-2 pb-2"><input type="number" step="any" name="items[0][debit]" class="debit-input w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 sm:text-sm" placeholder="0.00"></td>
                                    <td class="pr-2 pb-2"><input type="number" step="any" name="items[0][credit]" class="credit-input w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 sm:text-sm" placeholder="0.00"></td>
                                    <td class="pb-2 text-center"></td>
                                </tr>
                                <!-- Line 2 -->
                                <tr class="item-row">
                                    <td class="pr-2 pb-2">
                                        <select name="items[1][account_id]" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                            <option value="">Select Account</option>
                                            @foreach($accounts as $acc)
                                                <option value="{{ $acc->id }}">{{ $acc->account_name }} ({{ $acc->account_type }})</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="pr-2 pb-2"><input type="number" step="any" name="items[1][debit]" class="debit-input w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 sm:text-sm" placeholder="0.00"></td>
                                    <td class="pr-2 pb-2"><input type="number" step="any" name="items[1][credit]" class="credit-input w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 sm:text-sm" placeholder="0.00"></td>
                                    <td class="pb-2 text-center"></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td class="pt-2">
                                        <button type="button" id="add-row-btn" class="text-sm font-medium text-blue-600 hover:text-blue-700 flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                            Add Line
                                        </button>
                                    </td>
                                    <td class="pt-2 text-right font-bold pr-2" id="total-debit">0.00</td>
                                    <td class="pt-2 text-right font-bold pr-2" id="total-credit">0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('add-journal-modal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700">Save Journal Entry</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tableBody = document.querySelector('#journal-items-table tbody');
            const addRowBtn = document.getElementById('add-row-btn');
            const totalDebitEl = document.getElementById('total-debit');
            const totalCreditEl = document.getElementById('total-credit');
            let rowIdx = 2;

            function calculateTotals() {
                let d = 0, c = 0;
                document.querySelectorAll('.debit-input').forEach(i => d += parseFloat(i.value) || 0);
                document.querySelectorAll('.credit-input').forEach(i => c += parseFloat(i.value) || 0);
                totalDebitEl.textContent = d.toFixed(2);
                totalCreditEl.textContent = c.toFixed(2);
                
                if (d !== c) {
                    totalDebitEl.classList.add('text-rose-600');
                    totalCreditEl.classList.add('text-rose-600');
                } else {
                    totalDebitEl.classList.remove('text-rose-600');
                    totalCreditEl.classList.remove('text-rose-600');
                }
            }

            tableBody.addEventListener('input', calculateTotals);

            addRowBtn.addEventListener('click', () => {
                const tr = document.createElement('tr');
                tr.className = 'item-row';
                tr.innerHTML = `
                    <td class="pr-2 pb-2">
                        <select name="items[${rowIdx}][account_id]" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">Select Account</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->account_name }} ({{ $acc->account_type }})</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="pr-2 pb-2"><input type="number" step="any" name="items[${rowIdx}][debit]" class="debit-input w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 sm:text-sm" placeholder="0.00"></td>
                    <td class="pr-2 pb-2"><input type="number" step="any" name="items[${rowIdx}][credit]" class="credit-input w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 sm:text-sm" placeholder="0.00"></td>
                    <td class="pb-2 text-center align-middle">
                        <button type="button" class="text-rose-400 hover:text-rose-600" onclick="this.closest('tr').remove(); calculateTotals();">
                            <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </td>
                `;
                tableBody.appendChild(tr);
                rowIdx++;
            });
        });
    </script>
</x-layouts::app>