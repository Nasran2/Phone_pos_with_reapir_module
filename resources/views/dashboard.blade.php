<x-layouts::app :title="__('Dashboard')">
    @php($currencySymbol = \App\Support\SystemSettings::currencySymbol())
    <!-- ApexCharts Library (Gives beautiful interactive charts with smooth curves) -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <!-- Top Dashboard Header Bar -->
    <div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <!-- Breadcrumb / Section Header -->
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-slate-850 dark:text-white">Dashboard</h2>
            @if($enableMultiStore)
                <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2 ml-4">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-widest">Store:</label>
                    <select name="store_id" onchange="this.form.submit()" class="rounded-xl border-slate-300 text-sm font-semibold shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-1">
                        <option value="">All Stores</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ $storeId == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>

        <!-- Search, Scanner, Notifications, and Date Picker -->
        <div class="flex items-center gap-4">
            <!-- Global Search Input -->
            <div class="relative hidden md:block w-72">
                <input 
                    type="text" 
                    placeholder="Search products, invoices, customers..." 
                    class="w-full pl-10 pr-10 py-2.5.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-slate-900 dark:text-white transition"
                />
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </div>
                <!-- Scanner Icon -->
                <button class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-blue-500 transition cursor-pointer" title="Scan Barcode">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M3 5h2v14H3zm4 0h1v14H7zm3 0h3v14h-3zm5 0h1v14h-1zm3 0h3v14h-3z"/>
                    </svg>
                </button>
            </div>

            <!-- Notifications Bell -->
            <div class="relative">
                <button class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-blue-500 transition rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700/50 cursor-pointer">
                    <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                </button>
                <span class="absolute top-1 right-1 h-2.5 w-2.5 bg-red-500 rounded-full ring-2 ring-white dark:ring-slate-800"></span>
            </div>

            <!-- Date Picker Card -->
            <div class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-300">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span>{{ now(\App\Support\SystemSettings::get('timezone', config('app.timezone'), 'business_settings'))->format(\App\Support\SystemSettings::get('date_format', 'Y-m-d', 'business_settings')) }}</span>
                <svg class="w-3.5 h-3.5 text-slate-400 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Body (Scrollable Content) -->
    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto">
        
        <!-- Upcoming Cheques Alert Banner -->
        @if(isset($upcomingCheques) && $upcomingCheques->count() > 0)
            <div class="bg-amber-50 border border-amber-200 border-l-4 border-l-amber-500 p-4 rounded-xl shadow-xs">
                <div class="flex items-start">
                    <div class="flex-shrink-0 mt-0.5">
                        <svg class="h-5 w-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3 w-full">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-extrabold text-amber-800 uppercase tracking-wide">Action Required: {{ $upcomingCheques->count() }} Upcoming Cheque(s)</h3>
                            <a href="{{ route('cheques.dashboard') }}" class="text-xs font-bold text-amber-700 bg-amber-100/50 hover:bg-amber-200 px-3 py-1.5 rounded-lg transition">View Dashboard &rarr;</a>
                        </div>
                        <div class="mt-2 text-xs text-amber-700 font-medium">
                            <ul class="list-disc pl-5 space-y-1.5">
                                @foreach($upcomingCheques->take(3) as $cheque)
                                    <li>
                                        <strong class="font-bold text-amber-900">{{ ucfirst($cheque->cheque_type) }} ({{ $cheque->cheque_type === 'incoming' ? ($cheque->received_from ?: 'Customer') : ($cheque->issued_to ?: 'Supplier') }})</strong> - 
                                        <span class="font-extrabold">{{ \App\Support\SystemSettings::money((float)$cheque->amount) }}</span> 
                                        <span class="text-amber-600 ml-1">
                                            (No: {{ $cheque->cheque_number }} | Bank: {{ $cheque->bank_name ?? 'N/A' }} | 
                                            Date: <span class="{{ $cheque->cheque_date < date('Y-m-d') ? 'text-red-600 font-bold' : ($cheque->cheque_date == date('Y-m-d') ? 'text-amber-800 font-bold' : '') }}">{{ $cheque->cheque_date }}</span>)
                                        </span>
                                    </li>
                                @endforeach
                                @if($upcomingCheques->count() > 3)
                                    <li class="pt-1"><em class="text-amber-600">And {{ $upcomingCheques->count() - 3 }} more cheque(s)...</em></li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- 5 Stats Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
            <!-- 1. Total Sales Today -->
            <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200/60 dark:border-slate-700/50 shadow-xs flex items-center justify-between group hover:shadow-md transition duration-200">
                <div class="space-y-2">
                    <span class="text-xs font-bold text-slate-400 dark:text-slate-500 tracking-wide uppercase">Total Sales (Today)</span>
                    <h3 class="text-lg font-extrabold text-slate-800 dark:text-white leading-tight">{{ \App\Support\SystemSettings::money((float) $totalSalesToday) }}</h3>
                    <div class="flex items-center gap-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <polyline points="18 15 12 9 6 15"/>
                        </svg>
                        <span>12.5%</span>
                        <span class="text-slate-400 font-semibold ml-0.5">vs Yesterday</span>
                    </div>
                </div>
                <div class="p-3.5 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 group-hover:scale-105 transition shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                </div>
            </div>

            <!-- 2. Retail Sales Today -->
            <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200/60 dark:border-slate-700/50 shadow-xs flex items-center justify-between group hover:shadow-md transition duration-200">
                <div class="space-y-2">
                    <span class="text-xs font-bold text-slate-400 dark:text-slate-500 tracking-wide uppercase">Retail Sales (Today)</span>
                    <h3 class="text-lg font-extrabold text-slate-800 dark:text-white leading-tight">{{ \App\Support\SystemSettings::money((float) $retailSalesToday) }}</h3>
                    <div class="flex items-center gap-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <polyline points="18 15 12 9 6 15"/>
                        </svg>
                        <span>10.8%</span>
                        <span class="text-slate-400 font-semibold ml-0.5">vs Yesterday</span>
                    </div>
                </div>
                <div class="p-3.5 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 group-hover:scale-105 transition shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/><path d="M14.31 8l5.74 9.94M9.69 8h11.48M7.38 12l5.74-9.94M9.69 16L3.95 6.06M14.31 16H2.83M16.62 12l-5.74 9.94"/>
                    </svg>
                </div>
            </div>

            <!-- 3. Wholesale Sales Today -->
            <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200/60 dark:border-slate-700/50 shadow-xs flex items-center justify-between group hover:shadow-md transition duration-200">
                <div class="space-y-2">
                    <span class="text-xs font-bold text-slate-400 dark:text-slate-500 tracking-wide uppercase">Wholesale Sales (Today)</span>
                    <h3 class="text-lg font-extrabold text-slate-800 dark:text-white leading-tight">{{ \App\Support\SystemSettings::money((float) $wholesaleSalesToday) }}</h3>
                    <div class="flex items-center gap-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <polyline points="18 15 12 9 6 15"/>
                        </svg>
                        <span>15.3%</span>
                        <span class="text-slate-400 font-semibold ml-0.5">vs Yesterday</span>
                    </div>
                </div>
                <div class="p-3.5 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 group-hover:scale-105 transition shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2 2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                    </svg>
                </div>
            </div>

            <!-- 4. Total Profit Today -->
            <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200/60 dark:border-slate-700/50 shadow-xs flex items-center justify-between group hover:shadow-md transition duration-200">
                <div class="space-y-2">
                    <span class="text-xs font-bold text-slate-400 dark:text-slate-500 tracking-wide uppercase">Total Profit (Today)</span>
                    <h3 class="text-lg font-extrabold text-slate-800 dark:text-white leading-tight">{{ \App\Support\SystemSettings::money((float) $totalProfitToday) }}</h3>
                    <div class="flex items-center gap-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <polyline points="18 15 12 9 6 15"/>
                        </svg>
                        <span>11.2%</span>
                        <span class="text-slate-400 font-semibold ml-0.5">vs Yesterday</span>
                    </div>
                </div>
                <div class="p-3.5 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 group-hover:scale-105 transition shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                </div>
            </div>

            <!-- 5. Total Purchase Today -->
            <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200/60 dark:border-slate-700/50 shadow-xs flex items-center justify-between group hover:shadow-md transition duration-200">
                <div class="space-y-2">
                    <span class="text-xs font-bold text-slate-400 dark:text-slate-500 tracking-wide uppercase">Total Purchase (Today)</span>
                    <h3 class="text-lg font-extrabold text-slate-800 dark:text-white leading-tight">{{ \App\Support\SystemSettings::money((float) $totalPurchaseToday) }}</h3>
                    <div class="flex items-center gap-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <polyline points="18 15 12 9 6 15"/>
                        </svg>
                        <span>8.7%</span>
                        <span class="text-slate-400 font-semibold ml-0.5">vs Yesterday</span>
                    </div>
                </div>
                <div class="p-3.5 rounded-xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 group-hover:scale-105 transition shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="1" y="3" width="15" height="13" rx="2" ry="2"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Central Analytics Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- 1. Sales Overview line chart -->
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200/60 dark:border-slate-700/50 shadow-xs lg:col-span-6 flex flex-col justify-between">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700/40 pb-4 mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-white">Sales Overview</h3>
                        <p class="text-[10px] text-slate-400 font-medium">Hourly transaction analytics</p>
                    </div>
                    <!-- Chart Toggles -->
                    <div class="flex items-center bg-slate-100 dark:bg-slate-900 rounded-lg p-0.5 text-[10px] font-bold text-slate-600 dark:text-slate-400">
                        <button class="px-2.5 py-1 bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-400 rounded-md shadow-xs">Today</button>
                        <button class="px-2.5 py-1">This Week</button>
                        <button class="px-2.5 py-1">This Month</button>
                        <button class="px-2.5 py-1">This Year</button>
                    </div>
                </div>
                <!-- Line Chart Mount Div -->
                <div class="w-full h-72" id="sales-line-chart"></div>
            </div>

            <!-- 2. Sales by Payment Method Chart -->
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200/60 dark:border-slate-700/50 shadow-xs lg:col-span-3 flex flex-col justify-between">
                <div class="border-b border-slate-100 dark:border-slate-700/40 pb-4 mb-4">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white">Sales by Payment Method</h3>
                    <p class="text-[10px] text-slate-400 font-medium">Collection allocation channels</p>
                </div>
                <!-- Donut Chart Mount Div -->
                <div class="w-full h-52 flex items-center justify-center" id="payment-donut-chart"></div>
                
                <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 border-t border-slate-100 dark:border-slate-700/40 pt-4 flex items-center justify-between mt-2">
                    <span>Total Transactions:</span>
                    <span class="text-slate-800 dark:text-white font-extrabold text-xs">86</span>
                </div>
            </div>

            <!-- 3. Summary details Card -->
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200/60 dark:border-slate-700/50 shadow-xs lg:col-span-3 flex flex-col justify-between">
                <div class="border-b border-slate-100 dark:border-slate-700/40 pb-4 mb-4">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white">System Summary</h3>
                    <p class="text-[10px] text-slate-400 font-medium">Business key metrics</p>
                </div>
                <div class="space-y-3.5 flex-1 flex flex-col justify-center">
                    <div class="flex items-center justify-between border-b border-slate-100/60 dark:border-slate-750 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Total Customers:</span>
                        </div>
                        <span class="text-xs font-extrabold text-slate-800 dark:text-white">{{ number_format($totalCustomers) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-100/60 dark:border-slate-750 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-red-500"></span>
                            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Customer Due:</span>
                        </div>
                        <span class="text-xs font-extrabold text-red-600 dark:text-red-400">{{ \App\Support\SystemSettings::money((float) $customerDue) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-100/60 dark:border-slate-750 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Total Suppliers:</span>
                        </div>
                        <span class="text-xs font-extrabold text-slate-800 dark:text-white">{{ number_format($totalSuppliers) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-100/60 dark:border-slate-750 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-red-500"></span>
                            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Supplier Due:</span>
                        </div>
                        <span class="text-xs font-extrabold text-red-600 dark:text-red-400">{{ \App\Support\SystemSettings::money((float) $supplierDue) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-100/60 dark:border-slate-750 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Total Expenses (Today):</span>
                        </div>
                        <span class="text-xs font-extrabold text-slate-800 dark:text-white">{{ \App\Support\SystemSettings::money((float) $totalExpensesToday) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-100/60 dark:border-slate-750 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Cash in Hand:</span>
                        </div>
                        <span class="text-xs font-extrabold text-emerald-600 dark:text-emerald-400">{{ \App\Support\SystemSettings::money((float) $cashInHand) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Bank Balance:</span>
                        </div>
                        <span class="text-xs font-extrabold text-slate-800 dark:text-white">{{ \App\Support\SystemSettings::money((float) $bankBalance) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Grid: Recent Sales, Purchases, and Stock Warning list -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- 1. Recent Sales Card -->
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200/60 dark:border-slate-700/50 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700/40 pb-4 mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-white">Recent Sales</h3>
                        <p class="text-[10px] text-slate-400 font-medium">Today's transactions</p>
                    </div>
                    <a href="#" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline">View All</a>
                </div>
                <div class="flex-1 overflow-x-auto min-h-64">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="text-slate-400 border-b border-slate-100 dark:border-slate-700/40 pb-2">
                                <th class="pb-2.5 font-bold">Invoice</th>
                                <th class="pb-2.5 font-bold">Customer</th>
                                <th class="pb-2.5 font-bold text-right">Amount</th>
                                <th class="pb-2.5 font-bold text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100/50 dark:divide-slate-700/30">
                            @forelse($recentSales as $sale)
                                <tr>
                                    <td class="py-3.5 font-semibold text-blue-600 dark:text-blue-400 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                                        </svg>
                                        <span>{{ $sale->invoice_no }}</span>
                                    </td>
                                    <td class="py-3.5 text-slate-600 dark:text-slate-300 font-semibold">{{ $sale->customer_name }}</td>
                                    <td class="py-3.5 text-right font-extrabold text-slate-800 dark:text-white">{{ \App\Support\SystemSettings::money((float) $sale->payable_amount) }}</td>
                                    <td class="py-3.5 text-right">
                                        @if($sale->due_amount == 0)
                                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 font-bold text-[9px] uppercase tracking-wide">Paid</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 font-bold text-[9px] uppercase tracking-wide">Due</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-slate-400 font-medium">No sales recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. Recent Purchases Card -->
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200/60 dark:border-slate-700/50 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700/40 pb-4 mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-white">Recent Purchases</h3>
                        <p class="text-[10px] text-slate-400 font-medium">Inventory intake</p>
                    </div>
                    <a href="#" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline">View All</a>
                </div>
                <div class="flex-1 overflow-x-auto min-h-64">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="text-slate-400 border-b border-slate-100 dark:border-slate-700/40 pb-2">
                                <th class="pb-2.5 font-bold">PO No</th>
                                <th class="pb-2.5 font-bold">Supplier</th>
                                <th class="pb-2.5 font-bold text-right">Amount</th>
                                <th class="pb-2.5 font-bold text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100/50 dark:divide-slate-700/30">
                            @forelse($recentPurchases as $purchase)
                                <tr>
                                    <td class="py-3.5 font-semibold text-blue-600 dark:text-blue-400 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                                        </svg>
                                        <span>{{ $purchase->invoice_no }}</span>
                                    </td>
                                    <td class="py-3.5 text-slate-600 dark:text-slate-300 font-semibold">{{ $purchase->supplier_name }}</td>
                                    <td class="py-3.5 text-right font-extrabold text-slate-800 dark:text-white">{{ \App\Support\SystemSettings::money((float) $purchase->total_amount) }}</td>
                                    <td class="py-3.5 text-right">
                                        @if($purchase->due_amount == 0)
                                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 font-bold text-[9px] uppercase tracking-wide">Paid</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 font-bold text-[9px] uppercase tracking-wide">Due</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-slate-400 font-medium">No purchases recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. Low Stock Alert List -->
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200/60 dark:border-slate-700/50 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700/40 pb-4 mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-white">Low Stock Alert</h3>
                        <p class="text-[10px] text-slate-400 font-medium">Replenishment list</p>
                    </div>
                    <a href="#" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline">View All</a>
                </div>
                <div class="flex-1 space-y-3.5 flex flex-col justify-center min-h-64">
                    @forelse($lowStockAlerts as $alert)
                        <div class="flex items-center justify-between pb-3.5 border-b border-slate-100/50 dark:border-slate-700/30 last:border-0 last:pb-0">
                            <div class="flex items-center gap-3">
                                <!-- Phone Accessory Image Stand-in Icon -->
                                <div class="h-9 w-9 rounded-lg bg-slate-100 dark:bg-slate-900 flex items-center justify-center text-slate-500 font-bold text-xs shrink-0">
                                    {{ substr($alert->name, 0, 2) }}
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-800 dark:text-white leading-snug">{{ $alert->name }}</h4>
                                    <p class="text-[10px] text-red-500 font-bold mt-1">Stock: {{ (int) $alert->current_stock }}</p>
                                </div>
                            </div>
                            <span class="h-6 w-6 rounded-full bg-red-100 dark:bg-red-950/60 text-red-600 dark:text-red-400 font-bold flex items-center justify-center text-xs shadow-xs">{{ (int) $alert->current_stock }}</span>
                        </div>
                    @empty
                        <div class="py-8 text-center text-slate-400 font-medium text-xs">All products have sufficient stock.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Very Bottom Widgets Bar (5 Blocks) -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-5 pt-3">
            <!-- Total Products -->
            <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200/60 dark:border-slate-700/50 shadow-xs flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-blue-100/70 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 shrink-0">
                    <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-slate-400 font-bold tracking-wide uppercase leading-none">Total Products</p>
                    <h4 class="text-sm font-black text-slate-800 dark:text-white mt-1">{{ number_format($totalProducts) }}</h4>
                </div>
            </div>

            <!-- Total Stock Value -->
            <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200/60 dark:border-slate-700/50 shadow-xs flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-emerald-100/70 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400 shrink-0">
                    <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-slate-400 font-bold tracking-wide uppercase leading-none">Total Stock Value</p>
                    <h4 class="text-sm font-black text-slate-800 dark:text-white mt-1">{{ \App\Support\SystemSettings::money((float) $totalStockValue) }}</h4>
                </div>
            </div>

            <!-- Today's Orders -->
            <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200/60 dark:border-slate-700/50 shadow-xs flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-amber-100/70 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400 shrink-0">
                    <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-slate-400 font-bold tracking-wide uppercase leading-none">Today's Orders</p>
                    <h4 class="text-sm font-black text-slate-800 dark:text-white mt-1">{{ $todayOrders }}</h4>
                </div>
            </div>

            <!-- Due Invoices -->
            <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200/60 dark:border-slate-700/50 shadow-xs flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-red-100/70 text-red-600 dark:bg-red-900/20 dark:text-red-400 shrink-0">
                    <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </div>
                <div>
                <div>
                    <p class="text-[10px] text-slate-400 font-bold tracking-wide uppercase leading-none">Due Invoices</p>
                    <h4 class="text-sm font-black text-slate-800 dark:text-white mt-1">{{ $dueInvoices }}</h4>
                </div>
            </div>

            <!-- Unread Notifications -->
            <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200/60 dark:border-slate-700/50 shadow-xs flex items-center gap-3 col-span-2 md:col-span-1">
                <div class="p-2.5 rounded-lg bg-indigo-100/70 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400 shrink-0">
                    <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-slate-400 font-bold tracking-wide uppercase leading-none">Unread Notifications</p>
                    <h4 class="text-sm font-black text-slate-800 dark:text-white mt-1">{{ $unreadNotifications }}</h4>
                </div>
            </div>
        </div>

    </div>

    <!-- Charts Loading Configurations -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const dashboardCurrency = @json($currencySymbol);
            // A. Hourly Sales overview line chart
            const salesLineOptions = {
                series: [
                    {
                        name: 'Retail Sales',
                        data: @json($retailSalesHourly)
                    },
                    {
                        name: 'Wholesale Sales',
                        data: @json($wholesaleSalesHourly)
                    }
                ],
                chart: {
                    type: 'area',
                    height: 280,
                    toolbar: { show: false },
                    zoom: { enabled: false }
                },
                colors: ['#2563eb', '#10b981'],
                dataLabels: { enabled: false },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.35,
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },
                xaxis: {
                    categories: @json($chartHours),
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: {
                        style: {
                            colors: '#94a3b8',
                            fontSize: '9px',
                            fontWeight: 600
                        }
                    }
                },
                yaxis: {
                    labels: {
                        formatter: function (val) {
                            return dashboardCurrency + ' ' + val.toLocaleString();
                        },
                        style: {
                            colors: '#94a3b8',
                            fontSize: '9px',
                            fontWeight: 600
                        }
                    }
                },
                grid: {
                    borderColor: '#e2e8f0',
                    strokeDashArray: 4
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'center',
                    fontSize: '11px',
                    fontFamily: 'Instrument Sans',
                    labels: { colors: '#94a3b8' }
                },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: function (val) {
                            return dashboardCurrency + ' ' + val.toLocaleString();
                        }
                    }
                }
            };
            const salesLineChart = new ApexCharts(document.querySelector("#sales-line-chart"), salesLineOptions);
            salesLineChart.render();

            // B. Sales by Payment method donut chart
            const paymentDonutOptions = {
                series: [
                    {{ $paymentsSummary['Cash'] }},
                    {{ $paymentsSummary['Card'] }},
                    {{ $paymentsSummary['Bank Transfer'] }},
                    {{ $paymentsSummary['QR / Mobile Pay'] }},
                    {{ $paymentsSummary['Other'] }}
                ],
                chart: {
                    type: 'donut',
                    height: 220
                },
                labels: ['Cash', 'Card', 'Bank Transfer', 'QR / Mobile Pay', 'Other'],
                colors: ['#2563eb', '#10b981', '#6366f1', '#f59e0b', '#64748b'],
                dataLabels: { enabled: false },
                legend: {
                    position: 'bottom',
                    fontSize: '9px',
                    fontFamily: 'Instrument Sans',
                    labels: { colors: '#94a3b8' }
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '68%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total',
                                    formatter: function () {
                                        return @json(\App\Support\SystemSettings::money((float) $totalSalesToday));
                                    },
                                    style: {
                                        fontSize: '11px',
                                        fontWeight: 'black',
                                        color: '#64748b'
                                    }
                                }
                            }
                        }
                    }
                },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: function (val) {
                            return dashboardCurrency + ' ' + val.toLocaleString();
                        }
                    }
                }
            };
            const paymentDonutChart = new ApexCharts(document.querySelector("#payment-donut-chart"), paymentDonutOptions);
            paymentDonutChart.render();
        });
    </script>
</x-layouts::app>
