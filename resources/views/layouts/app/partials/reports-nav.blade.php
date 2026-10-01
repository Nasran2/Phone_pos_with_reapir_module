@php
    $reportGroups = [
        [
            'title' => 'Sales Reports',
            'color' => 'bg-blue-500',
            'links' => [
                ['label' => 'Sales Report', 'route' => 'reports.sales', 'permission' => 'reports.sales'],
                ['label' => 'Retail Sales Report', 'route' => 'reports.retail_sales', 'permission' => 'reports.retail_sales'],
                ['label' => 'Wholesale Sales Report', 'route' => 'reports.wholesale_sales', 'permission' => 'reports.wholesale_sales'],
                ['label' => 'Sales Return Report', 'route' => 'reports.sales_returns', 'permission' => 'reports.sales_returns'],
            ],
        ],
        [
            'title' => 'Purchase Reports',
            'color' => 'bg-emerald-500',
            'links' => [
                ['label' => 'Purchase Report', 'route' => 'reports.purchases', 'permission' => 'reports.purchase'],
                ['label' => 'Purchase Return Report', 'route' => 'reports.purchase_returns', 'permission' => 'reports.purchase_returns'],
            ],
        ],
        [
            'title' => 'Stock Reports',
            'color' => 'bg-orange-500',
            'links' => [
                ['label' => 'Stock Report', 'route' => 'reports.stock', 'permission' => 'reports.stock'],
                ['label' => 'Low Stock Report', 'route' => 'reports.low_stock', 'permission' => 'reports.low_stock'],
                ['label' => 'Stock Movement Report', 'route' => 'reports.stock_movement', 'permission' => 'reports.stock_movement'],
                ['label' => 'Damaged Stock Report', 'route' => 'reports.damaged_stock', 'permission' => 'reports.damaged_stock'],
            ],
        ],
        [
            'title' => 'Finance Reports',
            'color' => 'bg-purple-500',
            'links' => [
                ['label' => 'Product Profit Report', 'route' => 'reports.product_profit', 'permission' => 'reports.product_profit'],
                ['label' => 'Profit & Loss Report', 'route' => 'reports.profit_loss', 'permission' => 'reports.profit_loss'],
                ['label' => 'Expense Report', 'route' => 'reports.expenses', 'permission' => 'reports.expense'],
                ['label' => 'Cash & Bank Report', 'route' => 'reports.cash_bank', 'permission' => 'reports.cash_bank'],
                ['label' => 'Tax Report', 'route' => 'reports.tax', 'permission' => 'reports.tax'],
                ['label' => 'Daily Closing Report', 'route' => 'reports.daily_closing', 'permission' => 'reports.daily_closing'],
            ],
        ],
        [
            'title' => 'Customer & Supplier Reports',
            'color' => 'bg-cyan-500',
            'links' => [
                ['label' => 'Customer Due Report', 'route' => 'reports.customer_due', 'permission' => 'reports.customer_due'],
                ['label' => 'Supplier Due Report', 'route' => 'reports.supplier_due', 'permission' => 'reports.supplier_due'],
                ['label' => 'Customer Payment Report', 'route' => 'reports.customer_payments', 'permission' => 'reports.customer_payment'],
                ['label' => 'Supplier Payment Report', 'route' => 'reports.supplier_payments', 'permission' => 'reports.supplier_payment'],
            ],
        ],
        [
            'title' => 'System Reports',
            'color' => 'bg-slate-500',
            'links' => [
                ['label' => 'User Activity Report', 'route' => 'reports.user_activity', 'permission' => 'reports.user_activity'],
            ],
        ],
    ];

    $user = auth()->user();
    $canReport = fn (array $link) => $user && $user->hasPermission($link['permission']);
@endphp

@if($user && $user->hasPermission('reports.view'))
    <a href="{{ route('reports.index') }}" class="mb-2 flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-black transition {{ request()->routeIs('reports.index') ? 'bg-blue-600 text-white shadow-lg shadow-blue-950/20' : 'text-slate-400 hover:bg-[#182647] hover:text-white' }}">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M8 17V9m5 8V5m5 12v-6" /></svg>
        <span>All Reports Dashboard</span>
    </a>
@endif

@foreach($reportGroups as $group)
    @php
        $visibleLinks = collect($group['links'])->filter($canReport);
        $isOpen = $visibleLinks->contains(fn ($link) => request()->routeIs($link['route']));
    @endphp

    @if($visibleLinks->isNotEmpty())
        <div class="pt-3">
            <button type="button" onclick="toggleSubmenu(this)" class="w-full flex items-center justify-between rounded-lg px-2 py-1.5 text-[10px] font-black uppercase tracking-wider text-slate-400 transition hover:bg-[#182647] hover:text-white">
                <span class="flex items-center gap-2">
                    <span class="h-3 w-1 rounded-full {{ $group['color'] }}"></span>
                    <span>{{ $group['title'] }}</span>
                </span>
                <svg class="w-3 h-3 chevron-icon transition {{ $isOpen ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div class="{{ $isOpen ? '' : 'hidden' }} mt-1 space-y-1 border-l border-slate-700/60 pl-2">
                @foreach($visibleLinks as $link)
                    <a href="{{ route($link['route']) }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold transition {{ request()->routeIs($link['route']) ? 'bg-blue-600 text-white shadow-lg shadow-blue-950/20' : 'text-slate-400 hover:bg-[#182647] hover:text-white' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ request()->routeIs($link['route']) ? 'bg-white' : $group['color'] }}"></span>
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
@endforeach
