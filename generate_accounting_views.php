<?php

$dirs = [
    __DIR__ . '/resources/views/accounting',
    __DIR__ . '/resources/views/accounting/accounts',
    __DIR__ . '/resources/views/accounting/expenses',
    __DIR__ . '/resources/views/accounting/journal',
    __DIR__ . '/resources/views/accounting/ledger',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// 1. Accounts View
$accountsIndex = <<<BLADE
<x-layouts::app title="Chart of Accounts">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Chart of Accounts</h1>
            <p class="text-sm text-slate-500">Manage all accounting ledger accounts</p>
        </div>
        <button onclick="document.getElementById('add-account-modal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium text-sm flex items-center gap-2 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add Account
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
    @if(\$errors->any())
        <div class="mb-4 bg-rose-50 text-rose-700 p-4 rounded-lg border border-rose-100 font-medium">
            <ul class="list-disc list-inside">
                @foreach(\$errors->all() as \$error)
                    <li>{{ \$error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Code</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Account Name</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Type</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse(\$accounts as \$account)
                        <tr class="hover:bg-slate-50">
                            <td class="p-4 font-medium text-slate-900">{{ \$account->code }}</td>
                            <td class="p-4 text-slate-600">{{ \$account->name }}</td>
                            <td class="p-4 text-slate-600 capitalize">{{ \$account->type }}</td>
                            <td class="p-4">
                                @if(\$account->is_active)
                                    <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-md text-xs font-bold">Active</span>
                                @else
                                    <span class="px-2 py-1 bg-rose-100 text-rose-700 rounded-md text-xs font-bold">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-8 text-center text-slate-500">No accounts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(\$accounts->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ \$accounts->links() }}
            </div>
        @endif
    </div>

    <!-- Add Account Modal -->
    <div id="add-account-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800 text-lg">Add New Account</h3>
                <button onclick="document.getElementById('add-account-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form action="{{ route('accounting.accounts.store') }}" method="POST">
                @csrf
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Account Code</label>
                        <input type="text" name="code" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Account Name</label>
                        <input type="text" name="name" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Account Type</label>
                        <select name="type" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="asset">Asset</option>
                            <option value="liability">Liability</option>
                            <option value="equity">Equity</option>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('add-account-modal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700">Save Account</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
BLADE;
file_put_contents($dirs[1] . '/index.blade.php', $accountsIndex);

// 2. Expenses View
$expensesIndex = <<<BLADE
<x-layouts::app title="Expenses">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Expenses</h1>
            <p class="text-sm text-slate-500">Record and manage company expenses</p>
        </div>
        <button onclick="document.getElementById('add-expense-modal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium text-sm flex items-center gap-2 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add Expense
        </button>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-emerald-50 text-emerald-700 p-4 rounded-lg border border-emerald-100 font-medium">
            {{ session('success') }}
        </div>
    @endif
    @if(\$errors->any())
        <div class="mb-4 bg-rose-50 text-rose-700 p-4 rounded-lg border border-rose-100 font-medium">
            <ul class="list-disc list-inside">
                @foreach(\$errors->all() as \$error)
                    <li>{{ \$error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Date</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Ref No</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Expense Account</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Paid From</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Amount</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Note</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse(\$expenses as \$expense)
                        <tr class="hover:bg-slate-50">
                            <td class="p-4 text-slate-600">{{ \$expense->date }}</td>
                            <td class="p-4 font-medium text-slate-900">{{ \$expense->reference_no }}</td>
                            <td class="p-4 text-slate-600">{{ \$expense->expenseAccount->name ?? '-' }}</td>
                            <td class="p-4 text-slate-600">{{ \$expense->paymentAccount->name ?? '-' }}</td>
                            <td class="p-4 font-bold text-rose-600 text-right">Rs. {{ number_format(\$expense->amount, 2) }}</td>
                            <td class="p-4 text-slate-600">{{ \$expense->note }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-500">No expenses found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(\$expenses->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ \$expenses->links() }}
            </div>
        @endif
    </div>

    <!-- Add Expense Modal -->
    <div id="add-expense-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800 text-lg">Record Expense</h3>
                <button onclick="document.getElementById('add-expense-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form action="{{ route('accounting.expenses.store') }}" method="POST">
                @csrf
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Date</label>
                        <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Reference No (Optional)</label>
                        <input type="text" name="reference_no" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-slate-700 mb-1">Expense Account</label>
                        <select name="expense_account_id" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">Select Expense Type</option>
                            @foreach(\$expenseAccounts as \$acc)
                                <option value="{{ \$acc->id }}">{{ \$acc->name }} ({{ \$acc->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-slate-700 mb-1">Paid From</label>
                        <select name="payment_account_id" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">Select Payment Method</option>
                            @foreach(\$paymentAccounts as \$acc)
                                <option value="{{ \$acc->id }}">{{ \$acc->name }} ({{ \$acc->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-slate-700 mb-1">Amount</label>
                        <input type="number" step="0.01" name="amount" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-slate-700 mb-1">Note</label>
                        <textarea name="note" rows="2" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('add-expense-modal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
BLADE;
file_put_contents($dirs[2] . '/index.blade.php', $expensesIndex);

// 3. Journal View
$journalIndex = <<<BLADE
<x-layouts::app title="Journal Entries">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Journal Entries</h1>
            <p class="text-sm text-slate-500">Manual double-entry records</p>
        </div>
        <button onclick="document.getElementById('add-journal-modal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium text-sm flex items-center gap-2 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            New Journal
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
    @if(\$errors->any())
        <div class="mb-4 bg-rose-50 text-rose-700 p-4 rounded-lg border border-rose-100 font-medium">
            <ul class="list-disc list-inside">
                @foreach(\$errors->all() as \$error)
                    <li>{{ \$error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Date</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Reference No</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Note</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse(\$journals as \$journal)
                        <tr class="hover:bg-slate-50">
                            <td class="p-4 text-slate-600 align-top">{{ \$journal->date }}</td>
                            <td class="p-4 font-medium text-slate-900 align-top">{{ \$journal->reference_no }}</td>
                            <td class="p-4 text-slate-600 align-top">{{ \$journal->note }}</td>
                            <td class="p-4 align-top">
                                <table class="w-full text-xs">
                                    @foreach(\$journal->details as \$detail)
                                        <tr>
                                            <td class="py-1 w-1/2">{{ \$detail->account->name ?? '-' }}</td>
                                            <td class="py-1 text-right text-emerald-600 w-1/4">{{ \$detail->debit > 0 ? number_format(\$detail->debit, 2) : '' }}</td>
                                            <td class="py-1 text-right text-rose-600 w-1/4">{{ \$detail->credit > 0 ? number_format(\$detail->credit, 2) : '' }}</td>
                                        </tr>
                                    @endforeach
                                </table>
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
        @if(\$journals->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ \$journals->links() }}
            </div>
        @endif
    </div>

    <!-- Add Journal Modal -->
    <div id="add-journal-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800 text-lg">New Journal Entry</h3>
                <button onclick="document.getElementById('add-journal-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form action="{{ route('accounting.journal-entries.store') }}" method="POST">
                @csrf
                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Date</label>
                            <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Note</label>
                            <input type="text" name="note" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label class="block text-sm font-bold text-slate-700 mb-2">Entries</label>
                        <div class="space-y-2">
                            @for(\$i=0; \$i<2; \$i++)
                            <div class="flex gap-2 items-center">
                                <div class="w-1/2">
                                    <select name="details[{{ \$i }}][account_id]" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="">Select Account</option>
                                        @foreach(\$accounts as \$acc)
                                            <option value="{{ \$acc->id }}">{{ \$acc->name }} ({{ \$acc->code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="w-1/4">
                                    <input type="number" step="0.01" name="details[{{ \$i }}][debit]" placeholder="Debit" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm text-right">
                                </div>
                                <div class="w-1/4">
                                    <input type="number" step="0.01" name="details[{{ \$i }}][credit]" placeholder="Credit" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm text-right">
                                </div>
                            </div>
                            @endfor
                        </div>
                        <p class="text-xs text-slate-500 mt-2">Note: Total Debits must equal Total Credits.</p>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('add-journal-modal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700">Save Journal</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
BLADE;
file_put_contents($dirs[3] . '/index.blade.php', $journalIndex);

// 4. Ledger View
$ledgerIndex = <<<BLADE
<x-layouts::app title="General Ledger">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">General Ledger</h1>
            <p class="text-sm text-slate-500">View detailed account ledger statements</p>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
        <form action="{{ route('accounting.ledger.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-700 mb-1">Select Account</label>
                <select name="account_id" required class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">-- Choose Account --</option>
                    @foreach(\$accounts as \$acc)
                        <option value="{{ \$acc->id }}" {{ request('account_id') == \$acc->id ? 'selected' : '' }}>{{ \$acc->code }} - {{ \$acc->name }} ({{ ucfirst(\$acc->type) }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ request('start_date', date('Y-m-01')) }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ request('end_date', date('Y-m-t')) }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="md:col-span-4 flex justify-end">
                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-6 py-2 rounded-lg font-bold text-sm transition-colors shadow-sm">
                    View Ledger
                </button>
            </div>
        </form>
    </div>

    @if(\$selectedAccount)
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
            <h3 class="font-bold text-lg text-slate-800">{{ \$selectedAccount->name }} ({{ \$selectedAccount->code }})</h3>
            <span class="px-3 py-1 bg-slate-200 text-slate-700 rounded-full text-xs font-bold capitalize">{{ \$selectedAccount->type }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-600 text-xs uppercase tracking-wider border-b border-slate-200">
                        <th class="p-4 font-bold">Date</th>
                        <th class="p-4 font-bold">Ref No</th>
                        <th class="p-4 font-bold">Note/Description</th>
                        <th class="p-4 font-bold text-right">Debit</th>
                        <th class="p-4 font-bold text-right">Credit</th>
                        <th class="p-4 font-bold text-right">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                    <tr class="bg-slate-50">
                        <td colspan="5" class="p-4 font-bold text-right text-slate-600">Opening Balance</td>
                        <td class="p-4 font-bold text-right text-slate-900">Rs. {{ number_format(\$openingBalance, 2) }}</td>
                    </tr>
                    @php \$runningBalance = \$openingBalance; @endphp
                    @forelse(\$ledgerEntries as \$entry)
                        @php
                            if (in_array(\$selectedAccount->type, ['asset', 'expense'])) {
                                \$runningBalance += \$entry->debit - \$entry->credit;
                            } else {
                                \$runningBalance += \$entry->credit - \$entry->debit;
                            }
                        @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-4">{{ \$entry->date }}</td>
                            <td class="p-4 font-medium">{{ \$entry->reference_no }}</td>
                            <td class="p-4">{{ \$entry->note }} {{ \$entry->description ? '- '.\$entry->description : '' }}</td>
                            <td class="p-4 text-right text-emerald-600 font-medium">{{ \$entry->debit > 0 ? 'Rs. '.number_format(\$entry->debit, 2) : '-' }}</td>
                            <td class="p-4 text-right text-rose-600 font-medium">{{ \$entry->credit > 0 ? 'Rs. '.number_format(\$entry->credit, 2) : '-' }}</td>
                            <td class="p-4 text-right font-bold text-slate-900">Rs. {{ number_format(\$runningBalance, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-500">No transactions in this period.</td>
                        </tr>
                    @endforelse
                    <tr class="bg-slate-100 font-bold border-t border-slate-200">
                        <td colspan="3" class="p-4 text-right text-slate-800">Total / Closing Balance</td>
                        <td class="p-4 text-right text-emerald-700">Rs. {{ number_format(\$ledgerEntries->sum('debit'), 2) }}</td>
                        <td class="p-4 text-right text-rose-700">Rs. {{ number_format(\$ledgerEntries->sum('credit'), 2) }}</td>
                        <td class="p-4 text-right text-slate-900 text-lg">Rs. {{ number_format(\$runningBalance, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @else
        <div class="text-center p-12 bg-white rounded-xl shadow-sm border border-slate-200">
            <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            <h2 class="text-lg font-bold text-slate-700 mb-2">No Account Selected</h2>
            <p class="text-slate-500">Please select an account from the dropdown above to view its ledger.</p>
        </div>
    @endif
</x-layouts::app>
BLADE;
file_put_contents($dirs[4] . '/index.blade.php', $ledgerIndex);

echo "Accounting views generated.\n";
