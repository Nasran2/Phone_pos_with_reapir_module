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
    @if($errors->any())
        <div class="mb-4 bg-rose-50 text-rose-700 p-4 rounded-lg border border-rose-100 font-medium">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <h4 class="text-sm font-medium text-slate-500 mb-1">Today Expenses</h4>
            <div class="text-2xl font-bold text-slate-900">Rs. {{ number_format($todayTotal ?? 0, 2) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <h4 class="text-sm font-medium text-slate-500 mb-1">This Month</h4>
            <div class="text-2xl font-bold text-blue-600">Rs. {{ number_format($monthTotal ?? 0, 2) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <h4 class="text-sm font-medium text-slate-500 mb-1">Cash Expenses</h4>
            <div class="text-2xl font-bold text-slate-900">Rs. {{ number_format($cashTotal ?? 0, 2) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <h4 class="text-sm font-medium text-slate-500 mb-1">Bank Expenses</h4>
            <div class="text-2xl font-bold text-slate-900">Rs. {{ number_format($bankTotal ?? 0, 2) }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
        <form action="{{ route('accounting.expenses.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
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
                <label class="block text-xs font-bold text-slate-700 mb-1">Category</label>
                <select name="category_id" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Payment Method</label>
                <select name="payment_method" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" onchange="this.form.submit()">
                    <option value="">All Methods</option>
                    <option value="Cash" {{ request('payment_method') == 'Cash' ? 'selected' : '' }}>Cash</option>
                    <option value="Bank Transfer" {{ request('payment_method') == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="Card" {{ request('payment_method') == 'Card' ? 'selected' : '' }}>Card</option>
                    <option value="Cheque" {{ request('payment_method') == 'Cheque' ? 'selected' : '' }}>Cheque</option>
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-xs font-bold text-slate-700 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title or ref..." class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            </div>
            <div>
                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg font-medium text-sm transition-colors shadow-sm">Filter</button>
                @if(request()->anyFilled(['date_filter', 'category_id', 'payment_method', 'search']) && request('date_filter') !== 'today')
                    <a href="{{ route('accounting.expenses.index') }}" class="ml-2 text-sm text-blue-600 hover:underline font-medium">Clear</a>
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
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Date</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Expense No</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Category</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Title</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Paid Via</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Amount</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($expenses as $expense)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-4 text-slate-600 text-sm">{{ $expense->expense_date->format('M d, Y') }}</td>
                            <td class="p-4 text-sm font-medium text-slate-900">{{ $expense->expense_no }}</td>
                            <td class="p-4 text-sm text-slate-600">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800">
                                    {{ $expense->category->name ?? '-' }}
                                </span>
                            </td>
                            <td class="p-4 text-sm text-slate-600 font-medium">{{ $expense->title }}</td>
                            <td class="p-4 text-sm text-slate-600">
                                {{ $expense->payment_method }}<br>
                                <span class="text-xs text-slate-400">{{ $expense->paidFrom->account_name ?? '' }}</span>
                            </td>
                            <td class="p-4 font-bold text-rose-600 text-right text-sm">Rs. {{ number_format($expense->amount, 2) }}</td>
                            <td class="p-4 text-center">
                                <form action="{{ route('accounting.expenses.destroy', $expense) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this expense? This will also reverse the journal entry.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-rose-500 hover:text-rose-700 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-500">
                                <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <p class="font-medium text-slate-900">No expenses found</p>
                                <p class="text-sm mt-1">Try adjusting your filters or record a new expense.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($expenses->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $expenses->links() }}
            </div>
        @endif
    </div>

    <!-- Add Expense Modal -->
    <div id="add-expense-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800 text-lg">Record Expense</h3>
                <button onclick="document.getElementById('add-expense-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form action="{{ route('accounting.expenses.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Expense Date *</label>
                        <input type="date" name="expense_date" value="{{ date('Y-m-d') }}" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Expense Title *</label>
                        <input type="text" name="title" required placeholder="e.g. Office Rent July" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm font-bold text-slate-700">Expense Category *</label>
                            <button type="button" onclick="document.getElementById('quick-add-category-modal').classList.remove('hidden')" class="text-xs font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                New
                            </button>
                        </div>
                        <select name="expense_category_id" id="expense_category_select" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Amount *</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 sm:text-sm">Rs.</span>
                            <input type="number" step="0.01" name="amount" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm pl-9">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Payment Method *</label>
                        <select name="payment_method" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Card">Card</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Online Payment">Online Payment</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Paid From Account *</label>
                        <select name="paid_from_account_id" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">Select Asset Account</option>
                            @foreach($paymentAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->account_name }} ({{ $acc->account_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-slate-700 mb-1">Expense Ledger Account *</label>
                        <select name="expense_account_id" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">Select Target Expense Account</option>
                            @foreach($expenseAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->account_name }} ({{ $acc->account_code }})</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-500 mt-1">This account will be debited in the journal entry.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Reference No (Optional)</label>
                        <input type="text" name="reference_no" placeholder="Cheque no, invoice no..." class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Attachment (Bill/Receipt)</label>
                        <input type="file" name="attachment" accept="image/*,.pdf" class="w-full rounded-lg border border-slate-300 text-sm file:mr-4 file:py-2.5 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-slate-700 mb-1">Description</label>
                        <textarea name="description" rows="2" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('add-expense-modal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 shadow-sm transition">Save Expense</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Add Category Modal -->
    <div id="quick-add-category-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800 text-lg">New Category</h3>
                <button onclick="document.getElementById('quick-add-category-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form id="quick-category-form" onsubmit="quickAddCategory(event)">
                @csrf
                <div class="p-6">
                    <label class="block text-sm font-bold text-slate-700 mb-1">Category Name *</label>
                    <input type="text" name="name" id="quick-category-name" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('quick-add-category-modal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Check if we need to open add modal
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('action') === 'add') {
                document.getElementById('add-expense-modal').classList.remove('hidden');
            }
        });

        async function quickAddCategory(e) {
            e.preventDefault();
            const form = e.target;
            const btn = form.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = 'Saving...';

            try {
                const response = await fetch("{{ route('accounting.expense-categories.store') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        name: document.getElementById('quick-category-name').value
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    const select = document.getElementById('expense_category_select');
                    const option = new Option(data.category.name, data.category.id, true, true);
                    select.add(option);
                    document.getElementById('quick-add-category-modal').classList.add('hidden');
                    document.getElementById('quick-category-name').value = '';
                } else {
                    alert(data.message || 'Error adding category');
                }
            } catch (err) {
                alert('An error occurred. Please check if the category name already exists.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Save';
            }
        }
    </script>
</x-layouts::app>