<x-layouts::app title="Chart of Accounts">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Chart of Accounts</h1>
            <p class="text-sm text-slate-500">Manage your financial ledger accounts</p>
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
        <form action="{{ route('accounting.accounts.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Account Type</label>
                <select name="type" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    @foreach(['Asset', 'Liability', 'Equity', 'Income', 'Expense'] as $type)
                        <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Status</label>
                <select name="status" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-xs font-bold text-slate-700 mb-1">Search Account</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search code or name..." class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            </div>
            <div>
                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg font-medium text-sm transition-colors shadow-sm">Search</button>
                @if(request()->anyFilled(['type', 'status', 'search']))
                    <a href="{{ route('accounting.accounts.index') }}" class="ml-2 text-sm text-blue-600 hover:underline font-medium">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Account Code</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Account Name</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Type</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Current Balance</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Status</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($accounts as $account)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-4 font-medium text-slate-900 text-sm">{{ $account->account_code }}</td>
                            <td class="p-4 text-sm text-slate-700 font-medium">{{ $account->account_name }}</td>
                            <td class="p-4 text-sm text-slate-600">
                                @php
                                    $typeColors = [
                                        'Asset' => 'bg-emerald-100 text-emerald-800',
                                        'Liability' => 'bg-orange-100 text-orange-800',
                                        'Equity' => 'bg-purple-100 text-purple-800',
                                        'Income' => 'bg-blue-100 text-blue-800',
                                        'Expense' => 'bg-rose-100 text-rose-800',
                                    ];
                                    $color = $typeColors[$account->account_type] ?? 'bg-slate-100 text-slate-800';
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $color }}">
                                    {{ $account->account_type }}
                                </span>
                            </td>
                            <td class="p-4 font-bold text-right text-sm {{ $account->current_balance < 0 ? 'text-rose-600' : 'text-slate-900' }}">
                                Rs. {{ number_format($account->current_balance, 2) }}
                            </td>
                            <td class="p-4 text-center">
                                @if($account->status)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">Active</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800">Inactive</span>
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                <form action="{{ route('accounting.accounts.destroy', $account) }}" method="POST" class="inline" onsubmit="return confirmAction(event, 'Are you sure you want to delete this account? Only accounts with no transactions can be deleted.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-rose-500 hover:text-rose-700 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-500">No accounts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($accounts->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $accounts->links() }}
            </div>
        @endif
    </div>

    <!-- Add Account Modal -->
    <div id="add-account-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800 text-lg">Create Ledger Account</h3>
                <button onclick="document.getElementById('add-account-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form action="{{ route('accounting.accounts.store') }}" method="POST">
                @csrf
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Account Code *</label>
                        <input type="text" name="account_code" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Account Name *</label>
                        <input type="text" name="account_name" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Account Type *</label>
                        <select name="account_type" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="Asset">Asset</option>
                            <option value="Liability">Liability</option>
                            <option value="Equity">Equity</option>
                            <option value="Income">Income</option>
                            <option value="Expense">Expense</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Opening Balance</label>
                        <input type="number" step="any" name="opening_balance" value="0.00" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-slate-700 mb-1">Description</label>
                        <textarea name="description" rows="2" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
                    </div>
                    <div class="md:col-span-2 flex items-center mt-2">
                        <input type="checkbox" name="status" id="status_checkbox" checked class="rounded border-slate-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <label for="status_checkbox" class="ml-2 text-sm text-slate-700 font-medium">Account is Active</label>
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