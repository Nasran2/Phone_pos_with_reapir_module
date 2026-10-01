<x-layouts::app :title="__('Dashboard')">
    @php
        $currencySymbol = \App\Support\SystemSettings::currencySymbol();
    @endphp
    <!-- ApexCharts Library (Gives beautiful interactive charts with smooth curves) -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <!-- Top Dashboard Header Bar -->
    <div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <!-- Breadcrumb / Section Header -->
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-black dark:text-white">Dashboard</h2>
            @if($enableMultiStore)
                <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-center gap-2 ml-4">
                    <input type="hidden" name="chart_period" value="{{ $chartPeriod }}">
                    @if(request('date'))
                        <input type="hidden" name="date" value="{{ request('date') }}">
                    @endif
                    <label class="text-xs font-bold text-black uppercase tracking-widest">Store:</label>
                    <select name="store_id" onchange="this.form.submit()" class="rounded-xl border-slate-900 text-sm font-semibold shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white py-1">
                        <option value="">All Stores</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ $storeId == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>

        <div class="dashboard-tools flex flex-wrap items-center gap-3 min-w-0">
            <x-global-search />
            @if(auth()->user()->hasPermission('cheques.view'))
            <details class="relative notification-panel">
                <summary class="mobile-touch-button cursor-pointer list-none" aria-label="Cheque alerts">🔔 <span class="text-xs font-bold">{{ $unreadNotifications }}</span></summary>
                <div class="notification-results absolute right-0 z-40 rounded-xl border bg-white p-4 shadow-lg">
                    <p class="text-sm font-bold mb-2">Current cheque alerts</p>
                    @forelse($upcomingCheques as $notification)
                        <a class="block py-3 border-b text-sm" href="{{ route('cheques.show', $notification->id) }}">{{ $notification->cheque_number }} · {{ $notification->cheque_date->format('d M Y') }} · {{ \App\Support\SystemSettings::money($notification->amount) }}</a>
                    @empty
                        <p class="text-sm text-black">No cheques require action.</p>
                    @endforelse
                    <a class="block py-3 text-blue-600" href="{{ route('cheques.dashboard') }}">View cheque dashboard</a>
                </div>
            </details>
            @endif
            <!-- Date Picker Card -->
            <form id="date-filter-form" method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-black dark:text-black">
                <input type="hidden" name="chart_period" value="{{ $chartPeriod }}">
                @if(request('store_id'))
                    <input type="hidden" name="store_id" value="{{ request('store_id') }}">
                @endif
                <svg class="w-4 h-4 text-black" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <input type="date" name="date" value="{{ $today }}" max="{{ date('Y-m-d') }}" onchange="document.getElementById('date-filter-form').submit()" class="bg-transparent border-none p-0 text-xs font-semibold focus:ring-0 cursor-pointer text-black dark:text-black" style="width: auto;">
            </form>
        </div>
    </div>

    <!-- Main Dashboard Body (Scrollable Content) -->
    <div class="dashboard-content p-6 md:p-8 space-y-6 flex-1 w-full min-w-0">
        
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
                                    <li class="flex flex-wrap items-center justify-between gap-2 pb-1.5 border-b border-amber-200/50 last:border-0 last:pb-0">
                                        <div>
                                            <strong class="font-bold text-amber-900">{{ ucfirst($cheque->cheque_type) }} ({{ $cheque->cheque_type === 'received' ? ($cheque->received_from ?: 'Customer') : ($cheque->issued_to ?: 'Supplier') }})</strong> - 
                                            <span class="font-extrabold">{{ \App\Support\SystemSettings::money((float)$cheque->amount) }}</span> 
                                            <span class="text-amber-600 ml-1">
                                                (No: {{ $cheque->cheque_number }} | Bank: {{ $cheque->bank_name ?? 'N/A' }} | 
                                                Date: <span class="{{ $cheque->cheque_date->lt(today()) ? 'text-red-600 font-bold' : ($cheque->cheque_date->isToday() ? 'text-amber-800 font-bold' : '') }}">{{ $cheque->cheque_date->format('d M Y') }}</span>)
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1.5 ml-auto sm:ml-0">
                                            <a href="{{ route('cheques.show', $cheque->id) }}" class="text-[10px] font-bold px-2 py-1 rounded bg-amber-200/50 text-amber-800 hover:bg-amber-300 transition">View</a>
                                            @if($cheque->cheque_date->lte(today()))
                                                @if(auth()->user()->hasPermission('cheques.mark_passed'))<button type="button" onclick="passCheque({{ $cheque->id }})" class="text-[10px] font-bold px-2 py-1 rounded bg-emerald-500 text-white hover:bg-emerald-600 shadow-sm transition">Pass</button>@endif
                                                @if(auth()->user()->hasPermission('cheques.mark_returned'))<button type="button" onclick="returnCheque({{ $cheque->id }})" class="text-[10px] font-bold px-2 py-1 rounded bg-rose-500 text-white hover:bg-rose-600 shadow-sm transition">Return</button>@endif
                                            @endif
                                        </div>
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
            
            <script>
            let chequeSubmitting = false;
            function appendChequeFields(form, fields) {
                fields._token = @json(csrf_token());
                Object.entries(fields).forEach(([name, value]) => {
                    const input = document.createElement('input');
                    input.type = 'hidden'; input.name = name; input.value = value;
                    form.appendChild(input);
                });
            }
            function passCheque(id) {
                Swal.fire({
                    title: 'Mark as Passed',
                    html: '<div class="text-left mt-2"><label class="block text-[10px] font-bold uppercase text-black mb-1 ml-1">Cleared Date</label><input id="swal-cdate" type="date" value="{{ date('Y-m-d') }}" class="swal2-input !mt-0 !mb-4 !text-sm"><label class="block text-[10px] font-bold uppercase text-black mb-1 ml-1">Deposit Reference (Optional)</label><input id="swal-cref" placeholder="e.g., TXN-8392" class="swal2-input !mt-0 !text-sm"></div>',
                    confirmButtonText: 'Pass Cheque',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    preConfirm: () => ({
                        date: document.getElementById('swal-cdate').value,
                        ref: document.getElementById('swal-cref').value
                    })
                }).then((result) => {
                    if (result.isConfirmed && !chequeSubmitting) {
                        chequeSubmitting = true;
                        let f = document.createElement('form');
                        f.method = 'POST'; f.action = '{{ url("cheques") }}/' + id + '/pass';
                        appendChequeFields(f, {cleared_date: result.value.date, deposit_reference: result.value.ref});
                        document.body.appendChild(f); f.submit();
                    }
                });
            }
            function returnCheque(id) {
                Swal.fire({
                    title: 'Mark as Returned',
                    html: '<div class="text-left mt-2"><label class="block text-[10px] font-bold uppercase text-black mb-1 ml-1">Return Date</label><input id="swal-rdate" type="date" value="{{ date('Y-m-d') }}" class="swal2-input !mt-0 !mb-4 !text-sm"><label class="block text-[10px] font-bold uppercase text-black mb-1 ml-1">Reason</label><select id="swal-rreason" class="swal2-select !mt-0 !mb-4 !w-full !text-sm flex"><option>Insufficient Funds</option><option>Signature Mismatch</option><option>Account Closed</option><option>Payment Stopped</option><option>Date Error</option><option>Technical Error</option><option>Other</option></select><label class="block text-[10px] font-bold uppercase text-black mb-1 ml-1">Return Charge (Optional)</label><input id="swal-rcharge" type="number" step="0.01" placeholder="0.00" class="swal2-input !mt-0 !text-sm"></div>',
                    confirmButtonText: 'Return Cheque',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    preConfirm: () => ({
                        date: document.getElementById('swal-rdate').value,
                        reason: document.getElementById('swal-rreason').value,
                        charge: document.getElementById('swal-rcharge').value
                    })
                }).then((result) => {
                    if (result.isConfirmed && !chequeSubmitting) {
                        chequeSubmitting = true;
                        let f = document.createElement('form');
                        f.method = 'POST'; f.action = '{{ url("cheques") }}/' + id + '/return';
                        appendChequeFields(f, {return_date: result.value.date, return_reason: result.value.reason, return_charge: result.value.charge});
                        document.body.appendChild(f); f.submit();
                    }
                });
            }
            </script>
        @endif

        @php
            $cards = [
                ['Total Sales', $totalSalesToday, route('reports.sales', $reportFilters), '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>'],
                ['Retail Sales', $retailSalesToday, route('reports.sales', $reportFilters + ['sale_type' => 'retail']), '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>'],
                ['Wholesale Sales', $wholesaleSalesToday, route('reports.sales', $reportFilters + ['sale_type' => 'wholesale']), '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>'],
                ['Gross Profit', $totalProfitToday, route('reports.profit_loss', $reportFilters), '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>'],
                ['Total Purchase', $totalPurchaseToday, route('reports.purchases', $reportFilters), '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>'],
            ];
        @endphp
        <p class="text-sm font-semibold text-black">Selected date: {{ $dateLabel }}</p>
        <div class="dashboard-stats grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3">
            @foreach($cards as [$label, $amount, $target, $icon])
                <a href="{{ $target }}" class="dashboard-stat group block bg-white p-4 rounded-xl border border-slate-200 hover:border-blue-400 hover:shadow-sm transition duration-300">
                    <div class="flex justify-between items-start gap-2">
                        <div class="min-w-0">
                            <span class="text-xs font-bold text-black uppercase">{{ $label }}</span>
                            <h3 class="mt-2 text-xl font-extrabold text-black truncate">{{ \App\Support\SystemSettings::money((float) $amount) }}</h3>
                            <p class="mt-2 text-xs text-black truncate">{{ $changes[$loop->index] }} · vs previous day</p>
                        </div>
                        <div class="text-black group-hover:text-blue-500/30 transition-colors duration-300 shrink-0">
                            {!! $icon !!}
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <!-- Secondary Widgets Bar (5 Blocks) -->
        <div class="dashboard-bottom-stats grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3 mt-3 mb-6">
            <!-- Total Products -->
            <a href="{{ route('products.index') }}" class="group bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 hover:border-blue-400 hover:shadow-sm transition duration-300 dark:border-slate-700/50 shadow-xs flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-blue-100/70 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 shrink-0 group-hover:bg-blue-200 transition">
                    <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-black font-bold tracking-wide uppercase leading-none">Total Products</p>
                    <h4 class="text-sm font-black text-black dark:text-white mt-1">{{ number_format($totalProducts) }}</h4>
                </div>
            </a>

            <!-- Total Stock Value -->
            <a href="{{ route('stock.index') }}" class="group bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 hover:border-emerald-400 hover:shadow-sm transition duration-300 dark:border-slate-700/50 shadow-xs flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-emerald-100/70 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400 shrink-0 group-hover:bg-emerald-200 transition">
                    <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-black font-bold tracking-wide uppercase leading-none">Total Stock Value</p>
                    <h4 class="text-sm font-black text-black dark:text-white mt-1">{{ \App\Support\SystemSettings::money((float) $totalStockValue) }}</h4>
                </div>
            </a>

            <!-- Orders -->
            <a href="{{ route('sales.index') }}" class="group bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 hover:border-amber-400 hover:shadow-sm transition duration-300 dark:border-slate-700/50 shadow-xs flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-amber-100/70 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400 shrink-0 group-hover:bg-amber-200 transition">
                    <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-black font-bold tracking-wide uppercase leading-none">Orders · {{ $dateLabel }}</p>
                    <h4 class="text-sm font-black text-black dark:text-white mt-1">{{ $todayOrders }}</h4>
                </div>
            </a>

            <!-- Due Invoices -->
            <a href="{{ route('sales.index', ['payment_status' => 'due']) }}" class="group bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 hover:border-red-400 hover:shadow-sm transition duration-300 dark:border-slate-700/50 shadow-xs flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-red-100/70 text-red-600 dark:bg-red-900/20 dark:text-red-400 shrink-0 group-hover:bg-red-200 transition">
                    <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-black font-bold tracking-wide uppercase leading-none">Due Invoices</p>
                    <h4 class="text-sm font-black text-black dark:text-white mt-1">{{ $dueInvoices }}</h4>
                </div>
            </a>

            <!-- Current Cheque Alerts -->
            <a href="{{ route('cheques.dashboard') }}" class="group bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 hover:border-indigo-400 hover:shadow-sm transition duration-300 dark:border-slate-700/50 shadow-xs flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-indigo-100/70 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400 shrink-0 group-hover:bg-indigo-200 transition">
                    <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-black font-bold tracking-wide uppercase leading-none">Current Cheque Alerts</p>
                    <h4 class="text-sm font-black text-black dark:text-white mt-1">{{ $unreadNotifications }}</h4>
                </div>
            </a>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- 1. Sales Overview line chart -->
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200/60 dark:border-slate-700/50 shadow-xs lg:col-span-6 flex flex-col justify-between">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700/40 pb-4 mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-black dark:text-white flex items-center gap-2">
                            Sales Overview
                            <a href="{{ route('reports.sales', $reportFilters) }}" class="text-[10px] font-bold text-blue-600 hover:underline bg-blue-50 px-2 py-0.5 rounded-full border border-blue-200 transition">View Report &rarr;</a>
                        </h3>
                        <p class="text-[10px] text-black font-medium">Selected period around {{ $dateLabel }}</p>
                    </div>
                    <!-- Chart Toggles -->
                    <div class="flex items-center bg-slate-100 dark:bg-slate-900 rounded-lg p-0.5 text-[10px] font-bold text-black dark:text-black">
                        <a href="{{ request()->fullUrlWithQuery(['chart_period' => 'today']) }}" class="px-2.5 py-1 block {{ $chartPeriod === 'today' ? 'bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-400 rounded-md shadow-xs' : '' }}">{{ $dateLabel === 'Today' ? 'Today' : 'Selected Day' }}</a>
                        <a href="{{ request()->fullUrlWithQuery(['chart_period' => 'week']) }}" class="px-2.5 py-1 block {{ $chartPeriod === 'week' ? 'bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-400 rounded-md shadow-xs' : '' }}">Week</a>
                        <a href="{{ request()->fullUrlWithQuery(['chart_period' => 'month']) }}" class="px-2.5 py-1 block {{ $chartPeriod === 'month' ? 'bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-400 rounded-md shadow-xs' : '' }}">Month</a>
                        <a href="{{ request()->fullUrlWithQuery(['chart_period' => 'year']) }}" class="px-2.5 py-1 block {{ $chartPeriod === 'year' ? 'bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-400 rounded-md shadow-xs' : '' }}">Year</a>
                    </div>
                </div>
                <!-- Line Chart Mount Div -->
                <div class="w-full min-w-0 h-72" id="sales-line-chart"></div>
            </div>

            <!-- 2. Payments by Method Chart -->
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200/60 dark:border-slate-700/50 shadow-xs lg:col-span-3 flex flex-col justify-between">
                <div class="border-b border-slate-100 dark:border-slate-700/40 pb-4 mb-4">
                    <h3 class="text-sm font-bold text-black dark:text-white flex items-center gap-2">
                        Payments by Method
                        <a href="{{ route('reports.cash_bank', $reportFilters) }}" class="text-[10px] font-bold text-blue-600 hover:underline bg-blue-50 px-2 py-0.5 rounded-full border border-blue-200 transition">View Report &rarr;</a>
                    </h3>
                    <p class="text-[10px] text-black font-medium">Cleared payments received on {{ $dateLabel }}</p>
                </div>
                <!-- Donut Chart Mount Div -->
                @if(array_sum($paymentsSummary) > 0)
                    <div class="w-full h-52 flex items-center justify-center" id="payment-donut-chart"></div>
                @else
                    <div class="rounded-xl bg-slate-50 p-4 text-sm text-black">
                        No cleared payments recorded for {{ $dateLabel }}. Unpaid sales and held cheques are not included.
                    </div>
                @endif
                <dl class="payment-method-totals space-y-2 mt-4">
                    @foreach($paymentsSummary as $method => $amount)
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <dt class="text-black">{{ $method }}</dt>
                            <dd class="font-bold text-black dark:text-white">{{ \App\Support\SystemSettings::money((float) $amount) }}</dd>
                        </div>
                    @endforeach
                </dl>
                
                <div class="text-[10px] font-bold text-black dark:text-black border-t border-slate-100 dark:border-slate-700/40 pt-4 flex items-center justify-between mt-2">
                    <span>Total Transactions:</span>
                    <span class="text-black dark:text-white font-extrabold text-xs">{{ $paymentTransactionCount }}</span>
                </div>
            </div>

            <!-- 3. Summary details Card -->
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200/60 dark:border-slate-700/50 shadow-xs lg:col-span-3 flex flex-col justify-between">
                <div class="border-b border-slate-100 dark:border-slate-700/40 pb-4 mb-4">
                    <h3 class="text-sm font-bold text-black dark:text-white flex items-center gap-2">
                        Summary
                        <a href="{{ route('reports.daily_closing', $reportFilters) }}" class="text-[10px] font-bold text-blue-600 hover:underline bg-blue-50 px-2 py-0.5 rounded-full border border-blue-200 transition">View Report &rarr;</a>
                    </h3>
                    <p class="text-[10px] text-black font-medium">Today activities for the Current balances: expenses for {{ $dateLabel }}</p>
                </div>
                <div class="space-y-3.5 flex-1 flex flex-col justify-center">
                    <div class="flex items-center justify-between border-b border-slate-100/60 dark:border-slate-750 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                            <span class="text-[11px] font-semibold text-black dark:text-black">Total Customers:</span>
                        </div>
                        <span class="text-xs font-extrabold text-black dark:text-white">{{ number_format($totalCustomers) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-100/60 dark:border-slate-750 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-red-500"></span>
                            <span class="text-[11px] font-semibold text-black dark:text-black">Customer Due:</span>
                        </div>
                        <span class="text-xs font-extrabold text-red-600 dark:text-red-400">{{ \App\Support\SystemSettings::money((float) $customerDue) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-100/60 dark:border-slate-750 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                            <span class="text-[11px] font-semibold text-black dark:text-black">Total Suppliers:</span>
                        </div>
                        <span class="text-xs font-extrabold text-black dark:text-white">{{ number_format($totalSuppliers) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-100/60 dark:border-slate-750 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-red-500"></span>
                            <span class="text-[11px] font-semibold text-black dark:text-black">Supplier Due:</span>
                        </div>
                        <span class="text-xs font-extrabold text-red-600 dark:text-red-400">{{ \App\Support\SystemSettings::money((float) $supplierDue) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-100/60 dark:border-slate-750 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                            <span class="text-[11px] font-semibold text-black dark:text-black">Total Expenses ({{ $dateLabel }}):</span>
                        </div>
                        <span class="text-xs font-extrabold text-black dark:text-white">{{ \App\Support\SystemSettings::money((float) $totalExpensesToday) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-100/60 dark:border-slate-750 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span class="text-[11px] font-semibold text-black dark:text-black">Cash in Hand:</span>
                        </div>
                        <span class="text-xs font-extrabold text-emerald-600 dark:text-emerald-400">{{ \App\Support\SystemSettings::money((float) $cashInHand) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                            <span class="text-[11px] font-semibold text-black dark:text-black">Bank Balance:</span>
                        </div>
                        <span class="text-xs font-extrabold text-black dark:text-white">{{ \App\Support\SystemSettings::money((float) $bankBalance) }}</span>
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
                        <h3 class="text-sm font-bold text-black dark:text-white">Recent Sales</h3>
                        <p class="text-[10px] text-black font-medium">Today's transactions</p>
                    </div>
                    <a href="{{ route('reports.sales', $reportFilters) }}" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline">View All</a>
                </div>
                <div class="flex-1 overflow-x-auto min-h-64">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="text-black border-b border-slate-100 dark:border-slate-700/40 pb-2">
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
                                        <svg class="w-4 h-4 text-black shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                                        </svg>
                                        <a href="{{ route('sales.show', $sale->id) }}">{{ $sale->invoice_no }}</a>
                                    </td>
                                    <td class="py-3.5 text-black dark:text-black font-semibold">{{ $sale->customer_name ?: 'Walk-in Customer' }}</td>
                                    <td class="py-3.5 text-right font-extrabold text-black dark:text-white">{{ \App\Support\SystemSettings::money((float) $sale->payable_amount) }}</td>
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
                                    <td colspan="4" class="py-8 text-center text-black font-medium">No sales recorded.</td>
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
                        <h3 class="text-sm font-bold text-black dark:text-white">Recent Purchases</h3>
                        <p class="text-[10px] text-black font-medium">Inventory intake</p>
                    </div>
                    <a href="{{ route('reports.purchases', $reportFilters) }}" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline">View All</a>
                </div>
                <div class="flex-1 overflow-x-auto min-h-64">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="text-black border-b border-slate-100 dark:border-slate-700/40 pb-2">
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
                                        <svg class="w-4 h-4 text-black shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                                        </svg>
                                        <a href="{{ route('purchases.show', $purchase->id) }}">{{ $purchase->invoice_no }}</a>
                                    </td>
                                    <td class="py-3.5 text-black dark:text-black font-semibold">{{ $purchase->supplier_name }}</td>
                                    <td class="py-3.5 text-right font-extrabold text-black dark:text-white">{{ \App\Support\SystemSettings::money((float) $purchase->total_amount) }}</td>
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
                                    <td colspan="4" class="py-8 text-center text-black font-medium">No purchases recorded.</td>
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
                        <h3 class="text-sm font-bold text-black dark:text-white">Low Stock Alert</h3>
                        <p class="text-[10px] text-black font-medium">Replenishment list</p>
                    </div>
                    <a href="{{ route('reports.low_stock', array_filter(['store_id' => $storeId])) }}" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline">View All</a>
                </div>
                <div class="flex-1 space-y-3.5 flex flex-col justify-start min-h-64">
                    @forelse($lowStockAlerts as $alert)
                        <div class="flex items-center justify-between pb-3.5 border-b border-slate-100/50 dark:border-slate-700/30 last:border-0 last:pb-0">
                            <div class="flex items-center gap-3">
                                <!-- Phone Accessory Image Stand-in Icon -->
                                <div class="h-9 w-9 rounded-lg bg-slate-100 dark:bg-slate-900 flex items-center justify-center text-black font-bold text-xs shrink-0">
                                    {{ substr($alert->name, 0, 2) }}
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-black dark:text-white leading-snug">{{ $alert->name }}</h4>
                                    <p class="text-[10px] text-red-500 font-bold mt-1">Stock: {{ (int) $alert->current_stock }}</p>
                                </div>
                            </div>
                            <span class="h-6 w-6 rounded-full bg-red-100 dark:bg-red-950/60 text-red-600 dark:text-red-400 font-bold flex items-center justify-center text-xs shadow-xs">{{ (int) $alert->current_stock }}</span>
                        </div>
                    @empty
                        <div class="py-8 text-center text-black font-medium text-xs">All products have sufficient stock.</div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

    <!-- Charts Loading Configurations -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            if (typeof ApexCharts === 'undefined') {
                document.querySelector('#sales-line-chart').textContent = 'Chart unavailable. Open the sales report for detailed totals.';
                return;
            }
            const dashboardCurrency = @json($currencySymbol);
            // A. Sales overview line chart
            const salesLineOptions = {
                series: [
                    {
                        name: 'Retail Sales',
                        data: @json($retailSalesChart)
                    },
                    {
                        name: 'Wholesale Sales',
                        data: @json($wholesaleSalesChart)
                    }
                ],
                chart: {
                    height: 280,
                    type: 'area',
                    fontFamily: 'Instrument Sans, sans-serif',
                    toolbar: { show: false },
                    zoom: { enabled: false }
                },
                colors: ['#3b82f6', '#10b981'],
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
                    categories: @json($chartLabels),
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
                    {{ $paymentsSummary['Cheque'] }},
                    {{ $paymentsSummary['Other'] }}
                ],
                chart: {
                    type: 'donut',
                    height: 220
                },
                labels: ['Cash', 'Card', 'Bank Transfer', 'QR / Mobile Pay', 'Cheque', 'Other'],
                colors: ['#2563eb', '#10b981', '#6366f1', '#f59e0b', '#a855f7', '#64748b'],
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
                                value: {
                                    fontSize: '14px',
                                    fontWeight: 'bold',
                                    color: '#334155'
                                },
                                total: {
                                    show: true,
                                    label: 'Total',
                                    formatter: function () {
                                        return @json(\App\Support\SystemSettings::money((float) array_sum($paymentsSummary)));
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
            if (document.querySelector("#payment-donut-chart")) {
            const paymentDonutChart = new ApexCharts(document.querySelector("#payment-donut-chart"), paymentDonutOptions);
            paymentDonutChart.render();
            }
        });
    </script>
</x-layouts::app>
