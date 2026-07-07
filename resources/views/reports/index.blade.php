@php
    $reportGroups = [
        [
            'title' => 'Sales Reports',
            'description' => 'Revenue, sales channels, returns, and payment performance.',
            'color' => '#2563EB',
            'soft' => '#EFF6FF',
            'routes' => [
                ['label' => 'Sales Report', 'route' => 'reports.sales', 'permission' => 'reports.sales', 'description' => 'All sales invoices and due status'],
                ['label' => 'Retail Sales', 'route' => 'reports.retail_sales', 'permission' => 'reports.retail_sales', 'description' => 'Retail counter performance'],
                ['label' => 'Wholesale Sales', 'route' => 'reports.wholesale_sales', 'permission' => 'reports.wholesale_sales', 'description' => 'Wholesale sales analysis'],
                ['label' => 'Sales Returns', 'route' => 'reports.sales_returns', 'permission' => 'reports.sales_returns', 'description' => 'Returned invoice activity'],
            ],
        ],
        [
            'title' => 'Purchase Reports',
            'description' => 'Supplier purchases, returns, payments, and payable movement.',
            'color' => '#10B981',
            'soft' => '#ECFDF5',
            'routes' => [
                ['label' => 'Purchase Report', 'route' => 'reports.purchases', 'permission' => 'reports.purchase', 'description' => 'Purchases and supplier dues'],
                ['label' => 'Purchase Returns', 'route' => 'reports.purchase_returns', 'permission' => 'reports.purchase_returns', 'description' => 'Supplier return values'],
            ],
        ],
        [
            'title' => 'Stock Reports',
            'description' => 'Inventory levels, movements, low stock, and damaged stock.',
            'color' => '#F59E0B',
            'soft' => '#FFFBEB',
            'routes' => [
                ['label' => 'Stock Report', 'route' => 'reports.stock', 'permission' => 'reports.stock', 'description' => 'Inventory and valuation'],
                ['label' => 'Low Stock', 'route' => 'reports.low_stock', 'permission' => 'reports.low_stock', 'description' => 'Reorder attention list'],
                ['label' => 'Stock Movement', 'route' => 'reports.stock_movement', 'permission' => 'reports.stock_movement', 'description' => 'Every stock transaction'],
                ['label' => 'Damaged Stock', 'route' => 'reports.damaged_stock', 'permission' => 'reports.damaged_stock', 'description' => 'Damage quantity and loss'],
            ],
        ],
        [
            'title' => 'Finance Reports',
            'description' => 'Profit, expenses, cash, tax, and daily closing operations.',
            'color' => '#8B5CF6',
            'soft' => '#F5F3FF',
            'routes' => [
                ['label' => 'Product Profit', 'route' => 'reports.product_profit', 'permission' => 'reports.product_profit', 'description' => 'Gross profit by product'],
                ['label' => 'Profit & Loss', 'route' => 'reports.profit_loss', 'permission' => 'reports.profit_loss', 'description' => 'Income and expense view'],
                ['label' => 'Expense Report', 'route' => 'reports.expenses', 'permission' => 'reports.expense', 'description' => 'Spending by category'],
                ['label' => 'Cash & Bank', 'route' => 'reports.cash_bank', 'permission' => 'reports.cash_bank', 'description' => 'Account balances'],
                ['label' => 'Tax Report', 'route' => 'reports.tax', 'permission' => 'reports.tax', 'description' => 'Collected and paid tax'],
                ['label' => 'Daily Closing', 'route' => 'reports.daily_closing', 'permission' => 'reports.daily_closing', 'description' => 'Cash reconciliation'],
            ],
        ],
        [
            'title' => 'Customer Reports',
            'description' => 'Customer dues, collections, supplier dues, and supplier payments.',
            'color' => '#06B6D4',
            'soft' => '#ECFEFF',
            'routes' => [
                ['label' => 'Customer Due', 'route' => 'reports.customer_due', 'permission' => 'reports.customer_due', 'description' => 'Outstanding customer balances'],
                ['label' => 'Supplier Due', 'route' => 'reports.supplier_due', 'permission' => 'reports.supplier_due', 'description' => 'Supplier payable balances'],
                ['label' => 'Customer Payments', 'route' => 'reports.customer_payments', 'permission' => 'reports.customer_payment', 'description' => 'Collection history'],
                ['label' => 'Supplier Payments', 'route' => 'reports.supplier_payments', 'permission' => 'reports.supplier_payment', 'description' => 'Supplier settlement history'],
            ],
        ],
        [
            'title' => 'System Reports',
            'description' => 'System audit trail, user activity, and operational visibility.',
            'color' => '#64748B',
            'soft' => '#F8FAFC',
            'routes' => [
                ['label' => 'User Activity', 'route' => 'reports.user_activity', 'permission' => 'reports.user_activity', 'description' => 'Audit user actions'],
            ],
        ],
    ];

    $user = auth()->user();
    $visibleGroups = collect($reportGroups)->map(function ($group) use ($user) {
        $group['routes'] = collect($group['routes'])->filter(fn ($report) => $user && $user->hasPermission($report['permission']))->values();
        return $group;
    })->filter(fn ($group) => $group['routes']->isNotEmpty())->values();
@endphp

<x-layouts::app title="Reports Dashboard">
    <div class="min-h-screen flex-1 bg-slate-100/80 p-6 dark:bg-slate-950 md:p-8">
        <div class="relative mb-8 overflow-hidden rounded-3xl border border-white/70 bg-white p-6 shadow-xl shadow-slate-200/70 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/20 md:p-8">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(37,99,235,0.18),_transparent_32%),radial-gradient(circle_at_bottom_right,_rgba(6,182,212,0.14),_transparent_28%)]"></div>
            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white shadow-lg shadow-blue-500/25">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M8 17V9m5 8V5m5 12v-6" /></svg>
                    </div>
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.26em] text-blue-600 dark:text-cyan-300">ERP Reports</p>
                        <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950 dark:text-white">Reports Dashboard</h1>
                        <p class="mt-2 max-w-2xl text-sm font-medium text-slate-500 dark:text-slate-400">Premium command center for sales, inventory, finance, customer, supplier, and system intelligence.</p>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-2xl bg-blue-50 px-4 py-3 text-center dark:bg-blue-950/40">
                        <div class="text-2xl font-black text-blue-600">{{ $visibleGroups->count() }}</div>
                        <div class="text-[10px] font-black uppercase tracking-wider text-blue-500">Sections</div>
                    </div>
                    <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-center dark:bg-emerald-950/40">
                        <div class="text-2xl font-black text-emerald-600">{{ $visibleGroups->sum(fn ($group) => $group['routes']->count()) }}</div>
                        <div class="text-[10px] font-black uppercase tracking-wider text-emerald-500">Reports</div>
                    </div>
                    <div class="rounded-2xl bg-purple-50 px-4 py-3 text-center dark:bg-purple-950/40">
                        <div class="text-2xl font-black text-purple-600">Live</div>
                        <div class="text-[10px] font-black uppercase tracking-wider text-purple-500">Filters</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            @forelse($visibleGroups as $group)
                <section class="group overflow-hidden rounded-3xl border border-white/70 bg-white shadow-lg shadow-slate-200/60 transition duration-300 hover:-translate-y-1 hover:shadow-2xl dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/20">
                    <div class="relative overflow-hidden p-6" style="background: linear-gradient(135deg, {{ $group['soft'] }} 0%, #ffffff 62%);">
                        <div class="absolute -right-10 -top-10 h-32 w-32 rounded-full opacity-10 blur-sm" style="background: {{ $group['color'] }}"></div>
                        <div class="relative flex items-start justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl text-white shadow-lg" style="background: {{ $group['color'] }}">
                                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5a2 2 0 012-2h10l4 4v12a2 2 0 01-2 2H6a2 2 0 01-2-2zM8 13h8M8 17h5M8 9h3" /></svg>
                                </div>
                                <div>
                                    <h2 class="text-xl font-black text-slate-950">{{ $group['title'] }}</h2>
                                    <p class="mt-1 text-sm font-medium text-slate-500">{{ $group['description'] }}</p>
                                </div>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-black" style="background: {{ $group['soft'] }}; color: {{ $group['color'] }}">{{ $group['routes']->count() }} reports</span>
                        </div>
                    </div>
                    <div class="grid gap-3 p-4 sm:grid-cols-2">
                        @foreach($group['routes'] as $report)
                            <a href="{{ route($report['route']) }}" class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4 transition duration-200 hover:-translate-y-0.5 hover:border-blue-100 hover:bg-white hover:shadow-lg dark:border-slate-800 dark:bg-slate-950 dark:hover:bg-slate-900">
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-white" style="background: {{ $group['color'] }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-black text-slate-900 dark:text-white">{{ $report['label'] }}</h3>
                                        <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">{{ $report['description'] }}</p>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-400">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M5 3h14v18H5z" /></svg>
                    </div>
                    <h2 class="mt-4 text-lg font-black text-slate-900 dark:text-white">No reports available</h2>
                    <p class="mt-1 text-sm text-slate-500">Your role does not currently have report permissions.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-layouts::app>
