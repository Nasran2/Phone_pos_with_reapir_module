<x-layouts::app :title="__('Customer Management')">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Top Navbar / Header -->
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-slate-800">Customers</h2>
            <span class="text-xs bg-blue-100 text-blue-800 font-semibold px-2.5 py-0.5 rounded-full">
                {{ $customers->total() }} Customers
            </span>
        </div>
        <div>
            @if(auth()->user()->hasPermission('customers.create'))
            <button onclick="openCreateModal()" class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition duration-150 shadow-sm cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>Add Customer</span>
            </button>
            @endif
        </div>
    </div>

    <!-- Main Content Panel -->
    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto bg-slate-50">
        
        <!-- Alerts block -->
        @if(session('success'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl flex items-center justify-between text-emerald-800 text-xs font-medium">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 cursor-pointer">✕</button>
        </div>
        @endif

        @if(session('error'))
        <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl flex items-center justify-between text-red-800 text-xs font-medium">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-800 cursor-pointer">✕</button>
        </div>
        @endif

        <!-- Summary Cards Group -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Customers -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-xs flex items-center gap-4">
                <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Customers</div>
                    <div class="text-xl font-extrabold text-slate-850 mt-0.5">{{ $summary['total_customers'] }}</div>
                </div>
            </div>

            <!-- Active Customers -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-xs flex items-center gap-4">
                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active Accounts</div>
                    <div class="text-xl font-extrabold text-slate-850 mt-0.5">{{ $summary['active_customers'] }}</div>
                </div>
            </div>

            <!-- Total Customer Due (Highlight in Red) -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-xs flex items-center gap-4">
                <div class="p-3 bg-rose-50 text-rose-600 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Due Balance</div>
                    <div class="text-xl font-extrabold text-rose-600 mt-0.5">Rs. {{ number_format($summary['total_due'], 2) }}</div>
                </div>
            </div>

            <!-- Today Payments -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-xs flex items-center gap-4">
                <div class="p-3 bg-blue-50 text-blue-650 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Today's Collections</div>
                    <div class="text-xl font-extrabold text-blue-650 mt-0.5">Rs. {{ number_format($summary['today_payments'], 2) }}</div>
                </div>
            </div>
        </div>

        <!-- Filter Bar Card -->
        <div class="bg-white rounded-xl border border-slate-200/60 p-4 shadow-xs">
            <form method="GET" action="{{ route('customers.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search bar -->
                <div class="relative">
                    <input 
                        type="text" 
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search by name, phone, email..." 
                        class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-slate-800 transition"
                    />
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </div>
                </div>

                <!-- Status Filter -->
                <div>
                    <select 
                        name="status" 
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-800 transition bg-white"
                    >
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Only</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                    </select>
                </div>

                <div></div>

                <!-- Submit & Reset Buttons -->
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-lg text-xs transition duration-150 cursor-pointer">
                        Filter
                    </button>
                    <a href="{{ route('customers.index') }}" class="py-2 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-xs text-center transition duration-150 cursor-pointer">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Customers Table Card -->
        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200/60 text-slate-650 text-[11px] font-bold uppercase tracking-wider">
                            <th class="px-6 py-4">Customer Name</th>
                            <th class="px-6 py-4">Phone</th>
                            <th class="px-6 py-4">Email</th>
                            <th class="px-6 py-4">Total Sales</th>
                            <th class="px-6 py-4">Due Balance</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Created Date</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 text-xs">
                        @forelse($customers as $customer)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4">
                                <a href="{{ route('customers.show', $customer->id) }}" class="font-bold text-blue-650 hover:underline">
                                    {{ $customer->name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 font-semibold text-slate-600">
                                {{ $customer->phone }}
                            </td>
                            <td class="px-6 py-4 text-slate-500">
                                {{ $customer->email ?: '-' }}
                            </td>
                            <td class="px-6 py-4 font-medium">
                                <span class="px-2 py-0.5 bg-slate-100 rounded text-slate-700 font-bold">
                                    {{ $customer->sales_count }} Sales
                                </span>
                            </td>
                            <td class="px-6 py-4 font-bold">
                                @if($customer->balance > 0)
                                <span class="text-rose-600">Rs. {{ number_format($customer->balance, 2) }}</span>
                                @else
                                <span class="text-emerald-600">Rs. 0.00</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($customer->status)
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Active
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800">
                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span> Inactive
                                </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-500">
                                {{ $customer->created_at ? $customer->created_at->format('Y-m-d') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Record Payment Button -->
                                    @if(auth()->user()->hasPermission('customers.payment'))
                                    <button 
                                        onclick="openPaymentModal({{ json_encode($customer) }})"
                                        class="px-2.5 py-1 text-[10px] font-extrabold bg-emerald-600 hover:bg-emerald-700 text-white rounded transition shadow-xs cursor-pointer flex items-center gap-1"
                                        title="Record Payment"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>Pay</span>
                                    </button>
                                    @endif

                                    <!-- WhatsApp Button -->
                                    <a 
                                        href="https://wa.me/{{ '94' . ltrim(preg_replace('/[^0-9]/', '', $customer->phone), '0') }}" 
                                        target="_blank"
                                        class="p-1 text-slate-400 hover:text-emerald-500 hover:bg-emerald-50 transition rounded-lg cursor-pointer"
                                        title="WhatsApp Contact"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.717-1.458L0 24zm6.59-4.846c1.6.95 3.488 1.459 5.419 1.46h.007c5.632 0 10.21-4.58 10.21-10.21 0-2.724-1.06-5.285-2.984-7.21C17.38 1.268 14.825.21 12.1.21h-.007C6.46.21 1.882 4.79 1.882 10.42c-.001 1.93.502 3.81 1.458 5.405L2.342 20.6l4.907-1.287zM17.472 14.382c-.3-.149-1.777-.878-2.046-.977-.27-.1-.466-.149-.663.149-.197.3-.765.977-.938 1.176-.173.2-.347.223-.647.074-.3-.149-1.266-.467-2.41-1.488-.89-.794-1.49-1.775-1.665-2.074-.173-.3-.018-.462.13-.61.135-.133.3-.347.45-.52.15-.173.2-.3.3-.5.1-.2.05-.375-.025-.524-.075-.15-.663-1.597-.91-2.193-.24-.58-.48-.5-.66-.51-.17-.01-.37-.01-.57-.01-.2 0-.52.075-.8.375-.27.3-1.05 1.03-1.05 2.5 0 1.48 1.07 2.9 1.22 3.1.15.2 2.11 3.22 5.11 4.52.71.31 1.27.49 1.7.63.72.23 1.37.2 1.89.12.58-.09 1.78-.73 2.03-1.43.25-.7.25-1.29.17-1.42-.08-.13-.27-.2-.57-.35z"/>
                                        </svg>
                                    </a>

                                    @if(auth()->user()->hasPermission('customers.edit'))
                                    <!-- Edit Customer -->
                                    <button 
                                        onclick="openEditModal({{ json_encode($customer) }})"
                                        class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition rounded-lg cursor-pointer"
                                        title="Edit Details"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    @endif

                                    @if(auth()->user()->hasPermission('customers.delete'))
                                    <!-- Delete Customer -->
                                    <button 
                                        onclick="confirmDelete({{ $customer->id }}, '{{ $customer->name }}')"
                                        class="p-1.5 text-slate-400 hover:text-red-650 hover:bg-red-50 transition rounded-lg cursor-pointer"
                                        title="Delete Customer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                    <form id="delete-form-{{ $customer->id }}" method="POST" action="{{ route('customers.destroy', $customer->id) }}" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/>
                                </svg>
                                <span class="font-bold">No customers found matching the search filters.</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            @if($customers->hasPages())
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200/60">
                {{ $customers->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- CUSTOMER ADD / EDIT MODAL -->
    <div id="customerModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden transition-all duration-300 scale-95 opacity-0 modal-container text-slate-800">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-bold text-slate-850" id="modalTitle">Add New Customer</h3>
                <button onclick="closeModal('customerModal')" class="text-slate-400 hover:text-slate-700 text-lg cursor-pointer">✕</button>
            </div>
            <form id="customerForm" method="POST" action="" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Customer Name *</label>
                        <input type="text" name="name" id="cust_name" required class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-slate-800" placeholder="e.g. Priyantha Perera">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Phone Number *</label>
                        <input type="text" name="phone" id="cust_phone" required class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-slate-800" placeholder="e.g. 0771234567">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Email Address</label>
                        <input type="email" name="email" id="cust_email" class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-slate-800" placeholder="name@domain.com">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Credit Limit (Rs.)</label>
                        <input type="number" step="any" name="credit_limit" id="cust_credit_limit" class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-slate-800" placeholder="e.g. 50000">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div id="openingBalanceField">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Opening Due Balance (Rs.)</label>
                        <input type="number" step="any" name="opening_balance" id="cust_opening_balance" class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-slate-800" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Status *</label>
                        <select name="status" id="cust_status" required class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-slate-800 bg-white">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Address</label>
                    <input type="text" name="address" id="cust_address" class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-slate-800" placeholder="e.g. Galle Road, Colombo 03">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Notes</label>
                    <textarea name="notes" id="cust_notes" rows="2" class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-slate-800" placeholder="Write any specific notes here..."></textarea>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50 -mx-6 -mb-6 p-4">
                    <button type="button" onclick="closeModal('customerModal')" class="px-4 py-2 border border-slate-200 text-slate-500 hover:bg-slate-50 text-xs font-bold rounded-lg transition duration-150 cursor-pointer">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition duration-150 shadow-sm cursor-pointer">Save Customer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- RECORD DUE PAYMENT MODAL -->
    <div id="paymentModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full overflow-hidden transition-all duration-300 scale-95 opacity-0 modal-container text-slate-800">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-bold text-slate-850">Record Customer Payment</h3>
                <button onclick="closeModal('paymentModal')" class="text-slate-400 hover:text-slate-700 text-lg cursor-pointer">✕</button>
            </div>
            <form id="paymentForm" method="POST" action="" class="p-6 space-y-4">
                @csrf
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-150 flex justify-between items-center text-xs">
                    <div>
                        <span class="text-slate-400 block font-semibold uppercase tracking-wider text-[9px]">Paying Customer</span>
                        <span class="font-bold text-slate-800" id="pay_cust_name">-</span>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-400 block font-semibold uppercase tracking-wider text-[9px]">Current Due</span>
                        <span class="font-bold text-rose-600" id="pay_cust_due">Rs. 0.00</span>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Payment Amount (Rs.) *</label>
                    <input type="number" step="any" min="0.01" name="amount" id="pay_amount" required class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-slate-800" placeholder="0.00">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Payment Date *</label>
                        <input type="date" name="payment_date" required value="{{ now()->toDateString() }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-blue-500 text-slate-800">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Payment Method *</label>
                        <select name="payment_method" required class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-slate-850 bg-white">
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Card">Card</option>
                            <option value="Cheque">Cheque</option>
                            <option value="QR Payment">QR Payment</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Reference / Slip Number</label>
                    <input type="text" name="reference_no" class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-slate-800" placeholder="e.g. Txn-45214">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Note</label>
                    <textarea name="note" rows="2" class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-slate-800" placeholder="Payment receipt details..."></textarea>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50 -mx-6 -mb-6 p-4">
                    <button type="button" onclick="closeModal('paymentModal')" class="px-4 py-2 border border-slate-200 text-slate-500 hover:bg-slate-50 text-xs font-bold rounded-lg transition duration-150 cursor-pointer">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition duration-150 shadow-sm cursor-pointer">Record Payment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- UI Scripts -->
    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            setTimeout(() => {
                const container = modal.querySelector('.modal-container');
                container.classList.remove('scale-95', 'opacity-0');
                container.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            const container = modal.querySelector('.modal-container');
            container.classList.remove('scale-100', 'opacity-100');
            container.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }, 150);
        }

        function openCreateModal() {
            document.getElementById('modalTitle').innerText = 'Add New Customer';
            document.getElementById('customerForm').action = "{{ route('customers.store') }}";
            document.getElementById('formMethod').value = 'POST';
            document.getElementById('openingBalanceField').style.display = 'block';

            // Clear values
            document.getElementById('cust_name').value = '';
            document.getElementById('cust_phone').value = '';
            document.getElementById('cust_email').value = '';
            document.getElementById('cust_address').value = '';
            document.getElementById('cust_credit_limit').value = '';
            document.getElementById('cust_opening_balance').value = '';
            document.getElementById('cust_status').value = '1';
            document.getElementById('cust_notes').value = '';

            openModal('customerModal');
        }

        function openEditModal(customer) {
            document.getElementById('modalTitle').innerText = `Edit Customer: ${customer.name}`;
            document.getElementById('customerForm').action = `/customers/${customer.id}`;
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('openingBalanceField').style.display = 'block'; // Allow editing opening balance too, handled gracefully by code

            // Set values
            document.getElementById('cust_name').value = customer.name;
            document.getElementById('cust_phone').value = customer.phone;
            document.getElementById('cust_email').value = customer.email || '';
            document.getElementById('cust_address').value = customer.address || '';
            document.getElementById('cust_credit_limit').value = customer.credit_limit || '';
            document.getElementById('cust_opening_balance').value = customer.opening_balance || '0.00';
            document.getElementById('cust_status').value = customer.status ? '1' : '0';
            document.getElementById('cust_notes').value = customer.notes || '';

            openModal('customerModal');
        }

        function openPaymentModal(customer) {
            document.getElementById('pay_cust_name').innerText = customer.name;
            document.getElementById('pay_cust_due').innerText = 'Rs. ' + parseFloat(customer.balance).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
            document.getElementById('pay_amount').max = customer.balance;
            document.getElementById('pay_amount').value = customer.balance;
            
            // Set form route
            document.getElementById('paymentForm').action = `/customers/${customer.id}/payments`;
            
            openModal('paymentModal');
        }

        function confirmDelete(id, name) {
            Swal.fire({
                title: 'Delete Customer?',
                text: `Are you sure you want to delete "${name}"? All associated transactions will be removed.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(`delete-form-${id}`).submit();
                }
            });
        }
    </script>
</x-layouts::app>
