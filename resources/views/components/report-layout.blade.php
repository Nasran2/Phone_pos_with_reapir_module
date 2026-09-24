@props(['title', 'summary' => [], 'chartData' => [], 'filters' => true, 'exportPdf' => false, 'exportExcel' => false, 'exportRoute' => null, 'resetRoute' => '#'])

@php
    $descriptions = [
        'Sales Report' => 'Detailed sales analysis and business performance',
        'Retail Sales Report' => 'Retail counter sales, payment status, and customer activity',
        'Wholesale Sales Report' => 'Wholesale invoice performance and due tracking',
        'Sales Return Report' => 'Returned sales, refund values, and customer impact',
        'Purchases Report' => 'Supplier purchases, due balances, and receiving activity',
        'Purchase Return Report' => 'Supplier returns and refund performance',
        'Stock Report' => 'Inventory valuation, pricing, and stock availability',
        'Low Stock Report' => 'Products that need replenishment attention',
        'Stock Movement Report' => 'Every stock movement across purchases, sales, returns, and adjustments',
        'Damaged Stock Report' => 'Damaged inventory quantities, reasons, and estimated loss',
        'Product Profit Report' => 'Product level revenue, cost, and gross profit',
        'Profit & Loss Report' => 'Income, expense, gross profit, and net profit overview',
        'Expense Report' => 'Expense categories, payment methods, and spending activity',
        'Cash & Bank Report' => 'Cash and bank balances across active accounts',
        'Tax Report' => 'Tax collected, tax paid, and net tax position',
        'Daily Closing Report' => 'Daily cash reconciliation and closing balance',
        'Customer Due Report' => 'Outstanding customer balances and collection priorities',
        'Supplier Due Report' => 'Supplier payable balances and settlement priorities',
        'Customer Payments Report' => 'Customer collections and payment references',
        'Supplier Payments Report' => 'Supplier payments and payable settlement history',
        'User Activity Report' => 'User actions and system activity history',
    ];

    $summaryStyles = [
        ['gradient' => 'from-blue-600 to-cyan-500', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 10v-1m9-4a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['gradient' => 'from-violet-600 to-purple-500', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        ['gradient' => 'from-emerald-600 to-green-500', 'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
        ['gradient' => 'from-teal-600 to-emerald-500', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
        ['gradient' => 'from-amber-500 to-orange-500', 'icon' => 'M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z'],
        ['gradient' => 'from-rose-600 to-red-500', 'icon' => 'M6 18L18 6M6 6l12 12'],
    ];

    $currentBalanceReport = in_array($title, ['Customer Due Report', 'Supplier Due Report', 'Cash & Bank Report']);
    $description = $descriptions[$title] ?? 'Advanced business report with filters, exports, and operational insights';
    $barChart = $chartData['bar'] ?? [];
    $barItems = collect($barChart['items'] ?? []);
    $barMax = max((float) $barItems->max('value'), 0);
    $donutChart = $chartData['donut'] ?? [];
    $donutItems = collect($donutChart['items'] ?? [])->filter(fn ($item) => (float) ($item['value'] ?? 0) > 0)->values();
    $donutTotal = (float) $donutItems->sum('value');
    $progressChart = $chartData['progress'] ?? [];
    $progressItems = collect($progressChart['items'] ?? []);
    $progressMax = max((float) $progressItems->max('value'), 0);
    $formatChartValue = fn ($value, $type = 'number') => $type === 'money' ? 'Rs. '.number_format((float) $value, 2) : number_format((float) $value, 2);

    $donutStops = [];
    $donutCursor = 0;
    foreach ($donutItems as $item) {
        $start = $donutCursor;
        $end = $donutCursor + (((float) $item['value'] / $donutTotal) * 100);
        $donutStops[] = ($item['color'] ?? '#2563EB').' '.$start.'% '.$end.'%';
        $donutCursor = $end;
    }
    $donutBackground = $donutTotal > 0 ? 'conic-gradient('.implode(',', $donutStops).')' : 'conic-gradient(#e2e8f0 0% 100%)';

    $enableMultiStore = \App\Models\Setting::get('enable_multi_store', false);
    $stores = $enableMultiStore ? \Illuminate\Support\Facades\DB::table('stores')->where('status', true)->get() : collect();
@endphp

<x-layouts::app :title="$title">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .report-shell table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .report-shell thead tr { position: sticky; top: 0; z-index: 5; background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%); }
        .report-shell th { white-space: nowrap; color: #475569; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; }
        .report-shell tbody tr { transition: background-color .2s ease, transform .2s ease; }
        .report-shell tbody tr:nth-child(even) { background: #f8fafc; }
        .report-shell tbody tr:hover { background: #eff6ff; }
        .report-shell td { vertical-align: middle; }
        .report-shell td:last-child, .report-shell th:last-child { border-right: 0; }
        .report-shell .pagination, .report-shell nav[role="navigation"] { padding: 16px; }
        @media print {
            .report-actions, .report-filter-card, .report-charts { display: none !important; }
            .report-shell { background: white !important; padding: 0 !important; }
        }
    </style>

    <div class="report-shell min-h-screen flex-1 bg-slate-100/80 dark:bg-slate-950">
        <div class="relative overflow-hidden border-b border-blue-100 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(37,99,235,0.16),_transparent_32%),radial-gradient(circle_at_top_right,_rgba(6,182,212,0.14),_transparent_30%)]"></div>
            <div class="relative px-6 py-6 md:px-8">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white shadow-lg shadow-blue-500/25">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 14l3-3 3 2 5-7M7 18h11" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.24em] text-blue-600 dark:text-cyan-300">Reports</p>
                            <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white md:text-3xl">{{ $title }}</h1>
                            <p class="mt-1 max-w-2xl text-sm font-medium text-slate-500 dark:text-slate-400">{{ $description }}</p>
                        </div>
                    </div>

                    <div class="report-actions flex flex-wrap items-center gap-2">
                        <a href="{{ url()->current() . '?' . http_build_query(request()->query()) }}" class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-white/80 px-4 py-2 text-xs font-black text-blue-600 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:bg-blue-50 hover:shadow-md dark:border-blue-500/30 dark:bg-slate-900 dark:text-blue-300">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-2.64-6.36M21 3v6h-6" /></svg>
                            Refresh
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['type' => 'pdf']) }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white/80 px-4 py-2 text-xs font-black text-slate-600 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-100 hover:shadow-md dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                            Print / PDF
                        </a>
                        @if($exportPdf)
                            <a href="{{ $exportRoute ? $exportRoute . '?type=pdf&' . http_build_query(request()->except('type')) : '#' }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-xs font-black text-red-600 shadow-sm transition hover:-translate-y-0.5 hover:bg-red-100 hover:shadow-md dark:border-red-500/30 dark:bg-red-950/40 dark:text-red-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" /><path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6M12 18v-6m0 6l-3-3m3 3l3-3" /></svg>
                                Export PDF
                            </a>
                        @endif
                        @if($exportExcel)
                            <a href="{{ $exportRoute ? $exportRoute . '?type=excel&' . http_build_query(request()->except('type')) : '#' }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-black text-emerald-600 shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-100 hover:shadow-md dark:border-emerald-500/30 dark:bg-emerald-950/40 dark:text-emerald-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" /><path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6M8 13l3 3m0 0l3-3m-3 3V8" /></svg>
                                Export Excel
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6 p-6 md:p-8">
            @if(count($summary) > 0)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach($summary as $label => $value)
                        @php $style = $summaryStyles[$loop->index % count($summaryStyles)]; @endphp
                        <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br {{ $style['gradient'] }} p-5 text-white shadow-lg shadow-slate-300/50 transition duration-300 hover:-translate-y-1 hover:shadow-xl dark:shadow-black/30">
                            <div class="absolute -right-8 -top-8 h-28 w-28 rounded-full bg-white/15 blur-sm transition group-hover:scale-110"></div>
                            <div class="relative flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wider text-white/75">{{ $label }}</p>
                                    <div class="mt-3 text-2xl font-black tracking-tight">{{ $value }}</div>
                                    <p class="mt-1 text-xs font-semibold text-white/70">{{ $currentBalanceReport ? 'Current balance' : 'Current filtered period' }}</p>
                                </div>
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/18 ring-1 ring-white/20">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $style['icon'] }}" /></svg>
                                </div>
                            </div>
                            <div class="relative mt-5 inline-flex items-center rounded-full bg-white/18 px-2.5 py-1 text-[11px] font-black text-white ring-1 ring-white/15">
                                {{ $chartData['badges'][$label] ?? 'Live data' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($filters)
                <div class="report-filter-card rounded-2xl border border-white/70 bg-white p-5 shadow-lg shadow-slate-200/70 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/20">
                    <details class="report-filters" open><summary class="cursor-pointer py-2 font-bold">Filters ({{ count(array_filter(request()->except(['page', 'type']), fn ($value) => filled($value) && $value !== 'all')) }})</summary><form method="GET" action="{{ url()->current() }}" class="report-filter-form flex flex-col gap-5">
                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Quick Filters</h2>
                                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Choose a period or refine with the fields below.</p>
                                </div>
                            </div>
                            <div class="report-quick-filters mt-3 flex flex-wrap gap-2"></div>
                        </div>

                        <div class="[&_label]:mb-1 [&_label]:text-xs [&_label]:font-black [&_label]:uppercase [&_label]:tracking-wider [&_label]:text-slate-500 [&_input]:rounded-xl [&_input]:border-slate-200 [&_input]:bg-slate-50 [&_input]:text-sm [&_input]:font-semibold [&_input]:shadow-sm [&_input]:transition [&_input]:focus:border-blue-400 [&_input]:focus:ring-blue-400 [&_select]:rounded-xl [&_select]:border-slate-200 [&_select]:bg-slate-50 [&_select]:text-sm [&_select]:font-semibold [&_select]:shadow-sm [&_select]:transition [&_select]:focus:border-blue-400 [&_select]:focus:ring-blue-400 dark:[&_input]:border-slate-700 dark:[&_input]:bg-slate-950 dark:[&_input]:text-slate-200 dark:[&_select]:border-slate-700 dark:[&_select]:bg-slate-950 dark:[&_select]:text-slate-200">
                            @if($enableMultiStore && !$currentBalanceReport)
                                <div class="mb-4">
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Store Filter</label>
                                    <select name="store_id" class="w-full md:w-1/4 rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                        <option value="">All Stores</option>
                                        @foreach($stores as $store)
                                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            {{ $filterContent ?? '' }}
                        </div>

                        <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                            <a href="{{ $resetRoute }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-600 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300">Reset</a>
                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-2 text-xs font-black text-white shadow-lg shadow-blue-500/20 transition hover:-translate-y-0.5 hover:bg-blue-700">Apply Filter</button>
                        </div>
                    </form></details>
                </div>
            @endif

            @if(request()->except(['page', 'type']))
                <div class="flex flex-wrap gap-2" aria-label="Active filters">
                    @foreach(request()->except(['page', 'type']) as $filterName => $filterValue)
                        @if(is_scalar($filterValue) && filled($filterValue) && $filterValue !== 'all')
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs text-blue-800">{{ ucfirst(str_replace('_', ' ', $filterName)) }}: {{ $filterValue }}</span>
                        @endif
                    @endforeach
                </div>
            @endif
            <div class="report-charts grid grid-cols-1 gap-4 lg:grid-cols-4">
                <div class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:col-span-2">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white">{{ $barChart['title'] ?? 'Trend' }}</h3>
                            <p class="text-xs font-medium text-slate-500">{{ $barChart['subtitle'] ?? 'Filtered report movement' }}</p>
                        </div>
                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-black text-blue-600">Real data</span>
                    </div>
                    @if($barMax > 0)
                        <div class="mt-5 flex h-28 items-end gap-2">
                            @foreach($barItems as $item)
                                @php $height = max(6, ((float) ($item['value'] ?? 0) / $barMax) * 100); @endphp
                                <div class="group/bar flex flex-1 flex-col items-center gap-1">
                                    <div class="w-full rounded-t-lg bg-gradient-to-t from-blue-600 to-cyan-400 opacity-85 transition group-hover/bar:opacity-100" title="{{ $item['label'] ?? '' }}: {{ $formatChartValue($item['value'] ?? 0, $barChart['type'] ?? 'number') }}" style="height: {{ $height }}%"></div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-2 flex justify-between gap-2 text-[10px] font-bold text-slate-400">
                            <span>{{ $barItems->first()['label'] ?? '' }}</span>
                            <span>{{ $barItems->last()['label'] ?? '' }}</span>
                        </div>
                    @else
                        <div class="mt-5 flex h-28 items-center justify-center rounded-2xl bg-slate-50 text-xs font-bold text-slate-400 dark:bg-slate-950">
                            No chart data for selected filters.
                        </div>
                    @endif
                </div>
                <div class="rounded-2xl border border-purple-100 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">{{ $donutChart['title'] ?? 'Report Mix' }}</h3>
                    <p class="text-xs font-medium text-slate-500">{{ $donutChart['subtitle'] ?? 'Filtered distribution' }}</p>
                    <div class="mx-auto mt-5 h-28 w-28 rounded-full p-4" style="background: {{ $donutBackground }}">
                        <div class="flex h-full w-full items-center justify-center rounded-full bg-white text-xs font-black text-slate-700 dark:bg-slate-900 dark:text-slate-200">{{ $formatChartValue($donutTotal, $donutChart['type'] ?? 'number') }}</div>
                    </div>
                    <div class="mt-4 space-y-1">
                        @forelse($donutItems as $item)
                            <div class="flex items-center justify-between gap-2 text-[11px] font-bold text-slate-500">
                                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full" style="background: {{ $item['color'] ?? '#2563EB' }}"></span>{{ $item['label'] ?? '-' }}</span>
                                <span>{{ $formatChartValue($item['value'] ?? 0, $donutChart['type'] ?? 'number') }}</span>
                            </div>
                        @empty
                            <div class="text-center text-xs font-bold text-slate-400">No distribution data.</div>
                        @endforelse
                    </div>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">{{ $progressChart['title'] ?? 'Health' }}</h3>
                    <p class="text-xs font-medium text-slate-500">{{ $progressChart['subtitle'] ?? 'Filtered value split' }}</p>
                    <div class="mt-6 space-y-3">
                        @forelse($progressItems as $item)
                            @php $width = $progressMax > 0 ? (((float) ($item['value'] ?? 0) / $progressMax) * 100) : 0; @endphp
                            <div>
                                <div class="mb-1 flex items-center justify-between gap-2 text-[11px] font-bold text-slate-500">
                                    <span>{{ $item['label'] ?? '-' }}</span>
                                    <span>{{ $formatChartValue($item['value'] ?? 0, $progressChart['type'] ?? 'number') }}</span>
                                </div>
                                <div class="h-3 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                    <div class="h-full rounded-full" style="width: {{ $width }}%; background: {{ $item['color'] ?? '#10B981' }}"></div>
                                </div>
                            </div>
                        @empty
                            <div class="flex h-24 items-center justify-center rounded-2xl bg-slate-50 text-xs font-bold text-slate-400 dark:bg-slate-950">
                                No health data.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/20">
                <div class="overflow-x-auto">
                    {{ $slot }}
                </div>
                @if(count($summary) > 0)
                    <div class="border-t border-blue-100 bg-blue-50/80 px-4 py-3 dark:border-slate-800 dark:bg-blue-950/20">
                        <div class="flex flex-wrap items-center justify-end gap-3">
                            @foreach($summary as $label => $value)
                                <div class="rounded-xl bg-white px-3 py-2 text-xs font-black text-slate-700 shadow-sm ring-1 ring-blue-100 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700">
                                    <span class="text-blue-600 dark:text-blue-300">{{ $label }}:</span>
                                    <span>{{ $value }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const labels = {
                today: 'Today',
                yesterday: 'Yesterday',
                this_week: 'This Week',
                last_week: 'Last Week',
                this_month: 'This Month',
                last_month: 'Last Month',
                this_year: 'This Year',
                custom: 'Custom'
            };

            document.querySelectorAll('.report-filter-form').forEach(function (form) {
                const dateSelect = form.querySelector('select[name="date_filter"]');
                const quickWrap = form.querySelector('.report-quick-filters');

                if (dateSelect && quickWrap) {
                    dateSelect.closest('div').classList.add('hidden');

                    Object.entries(labels).forEach(function ([value, label]) {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.textContent = label;
                        button.dataset.value = value;
                        button.className = 'rounded-full px-3.5 py-2 text-xs font-black transition hover:-translate-y-0.5 hover:shadow-md';

                        if ((dateSelect.value || 'today') === value) {
                            button.className += ' bg-blue-600 text-white shadow-lg shadow-blue-500/20';
                        } else {
                            button.className += ' bg-slate-100 text-slate-600 hover:bg-blue-50 hover:text-blue-600';
                        }

                        button.addEventListener('click', function () {
                            dateSelect.value = value;
                            if (value === 'custom') { form.querySelector('input[type=date]')?.focus(); return; }
                            form.querySelectorAll('input[type=date]').forEach(input => input.value = '');
                            form.requestSubmit();
                        });

                        quickWrap.appendChild(button);
                    });
                }

                form.querySelectorAll('input[type="date"]').forEach(function (input) {
                    input.addEventListener('change', function () {
                        if (dateSelect) dateSelect.value = 'custom';
                    });
                });
                form.addEventListener('submit', function () {
                    if (dateSelect && (form.start_date?.value || form.end_date?.value)) dateSelect.value = 'custom';
                });

            });
        });
    </script>
</x-layouts::app>
