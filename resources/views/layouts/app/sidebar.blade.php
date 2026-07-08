<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
<head>
    @include('partials.head')
    <script>
        // Force light mode for the dashboard and ERP content area
        document.documentElement.classList.remove('dark');
        document.documentElement.classList.add('light');
    </script>
    <!-- Alpine.js (standard Laravel helper or lightweight CDN if needed, but simple vanilla JS is loaded easily) -->
</head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-100 antialiased font-sans flex flex-col lg:flex-row">
    @php
        $reportPermissions = [
            'reports.view',
            'reports.sales',
            'reports.retail_sales',
            'reports.wholesale_sales',
            'reports.sales_returns',
            'reports.purchase',
            'reports.purchase_returns',
            'reports.stock',
            'reports.low_stock',
            'reports.stock_movement',
            'reports.damaged_stock',
            'reports.product_profit',
            'reports.profit_loss',
            'reports.expense',
            'reports.cash_bank',
            'reports.tax',
            'reports.daily_closing',
            'reports.customer_due',
            'reports.supplier_due',
            'reports.customer_payment',
            'reports.supplier_payment',
            'reports.user_activity',
        ];
        $canAccessReports = auth()->check() && collect($reportPermissions)->contains(fn ($permission) => auth()->user()->hasPermission($permission));
    @endphp
    
    <!-- Desktop Sidebar (Hidden on mobile) -->
    <aside class="w-64 bg-[#0c1630] text-slate-300 flex flex-col border-r border-[#182647] h-screen max-h-screen overflow-hidden sticky top-0 left-0 hidden lg:flex shrink-0 z-40">
        <!-- Brand Logo Header -->
        <div class="flex items-center gap-3 px-6 py-5 border-b border-[#182647] bg-[#091024] shrink-0">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 shadow-lg shadow-blue-500/20 shrink-0">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <rect x="2" y="3" width="20" height="12" rx="2" />
                        <path d="M12 15v4" />
                        <path d="M9 19h6" />
                        <path d="M19 8h-2l-1.5-2H8.5L7 8H5" />
                        <circle cx="10" cy="8" r="1" />
                        <circle cx="14" cy="8" r="1" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-white tracking-wide leading-tight">Phone Shop POS</h2>
                    <p class="text-[10px] text-slate-400 font-semibold tracking-wider uppercase mt-0.5">Retail & Wholesale</p>
                </div>
        </div>

        <!-- Sidebar Nav Links -->
        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain custom-scrollbar pb-6">
            <nav class="p-4 space-y-1">
                <!-- Dashboard Link -->
                @if (auth()->user()->hasPermission('dashboard.view'))
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/10' : 'hover:bg-[#182647] hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                    </svg>
                    <span>Dashboard</span>
                </a>
                @endif

                <!-- User Management Dropdown -->
                @if (auth()->user()->hasPermission('users.view') || auth()->user()->hasPermission('roles.view') || auth()->user()->hasPermission('permissions.view') || auth()->user()->hasPermission('activity_logs.view'))
                <div>
                    <button onclick="toggleSubmenu(this)" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-[#182647] hover:text-white transition group focus:outline-none {{ request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('permissions.*') || request()->routeIs('activity-logs.*') ? 'bg-[#182647]/50 text-white' : '' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-white transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                            <span>User Management</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-500 group-hover:text-white transition chevron-icon {{ request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('permissions.*') || request()->routeIs('activity-logs.*') ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>
                    <div class="pl-10 pr-2 py-1 space-y-1 bg-[#091024]/40 rounded-lg mt-0.5 {{ request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('permissions.*') || request()->routeIs('activity-logs.*') ? '' : 'hidden' }}">
                        @if (auth()->user()->hasPermission('users.view'))
                        <a href="{{ route('users.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('users.*') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">Users</a>
                        @endif
                        @if (auth()->user()->hasPermission('roles.view'))
                        <a href="{{ route('roles.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('roles.*') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">Roles</a>
                        @endif
                        @if (auth()->user()->hasPermission('permissions.view'))
                        <a href="{{ route('permissions.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('permissions.*') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">Permissions</a>
                        @endif
                        @if (auth()->user()->hasPermission('activity_logs.view'))
                        <a href="{{ route('activity-logs.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('activity-logs.*') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">Activity Logs</a>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Products Dropdown -->
                @if (auth()->user()->hasPermission('products.view'))
                <div>
                    <button onclick="toggleSubmenu(this)" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-[#182647] hover:text-white transition group focus:outline-none {{ request()->routeIs('products.*') || request()->routeIs('categories.*') || request()->routeIs('brands.*') || request()->routeIs('units.*') ? 'bg-[#182647]/50 text-white' : '' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-white transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                            </svg>
                            <span>Products</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-500 group-hover:text-white transition chevron-icon {{ request()->routeIs('products.*') || request()->routeIs('categories.*') || request()->routeIs('brands.*') || request()->routeIs('units.*') ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>
                    <div class="pl-10 pr-2 py-1 space-y-1 bg-[#091024]/40 rounded-lg mt-0.5 {{ request()->routeIs('products.*') || request()->routeIs('categories.*') || request()->routeIs('brands.*') || request()->routeIs('units.*') ? '' : 'hidden' }}">
                        <a href="{{ route('products.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('products.index') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">All Products</a>
                        <a href="{{ route('categories.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('categories.*') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">Categories</a>
                        <a href="{{ route('brands.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('brands.*') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">Brands</a>
                        <a href="{{ route('units.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('units.*') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">Units</a>
                    </div>
                </div>
                @endif

                <!-- Purchase Dropdown -->
                @if (auth()->user()->hasPermission('purchase.view'))
                <div>
                    <button onclick="toggleSubmenu(this)" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-[#182647] hover:text-white transition group focus:outline-none {{ request()->routeIs('purchases.*') || request()->routeIs('purchase-returns.*') ? 'bg-[#182647]/50 text-white' : '' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-white transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <rect x="3" y="4" width="18" height="16" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            <span>Purchase</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-500 group-hover:text-white transition chevron-icon {{ request()->routeIs('purchases.*') || request()->routeIs('purchase-returns.*') ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>
                    <div class="pl-10 pr-2 py-1 space-y-1 bg-[#091024]/40 rounded-lg mt-0.5 {{ request()->routeIs('purchases.*') || request()->routeIs('purchase-returns.*') ? '' : 'hidden' }}">
                        <a href="{{ route('purchases.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('purchases.*') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">Purchases</a>
                        @if (auth()->user()->hasPermission('purchase.return'))
                        <a href="{{ route('purchase-returns.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('purchase-returns.*') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">Returns</a>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Sales Dropdown -->
                @if (auth()->user()->hasPermission('sales.view'))
                <div>
                    <button onclick="toggleSubmenu(this)" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-[#182647] hover:text-white transition group focus:outline-none">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-white transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                            </svg>
                            <span>Sales</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-500 group-hover:text-white transition chevron-icon {{ request()->routeIs('sales.*') || request()->routeIs('sales-returns.*') || request()->routeIs('hold-sales.*') ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>
                    <div class="pl-10 pr-2 py-1 space-y-1 bg-[#091024]/40 rounded-lg mt-0.5 {{ request()->routeIs('sales.*') || request()->routeIs('sales-returns.*') || request()->routeIs('hold-sales.*') ? '' : 'hidden' }}">
                        <a href="{{ route('sales.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('sales.*') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">Sales Ledger</a>
                        @if (auth()->user()->hasPermission('sales_return.view'))
                        <a href="{{ route('sales-returns.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('sales-returns.*') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">Returns</a>
                        @endif
                        @if (auth()->user()->hasPermission('hold_sales.view'))
                        <a href="{{ route('hold-sales.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('hold-sales.*') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">Hold Sales</a>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Customers Link -->
                @if (auth()->user()->hasPermission('customers.view'))
                <a href="{{ route('customers.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('customers.*') ? 'bg-[#182647] text-white' : 'hover:bg-[#182647] hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('customers.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                    <span>Customers</span>
                </a>
                @endif

                <!-- Suppliers Link -->
                @if (auth()->user()->hasPermission('suppliers.view'))
                <a href="{{ route('suppliers.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('suppliers.*') ? 'bg-[#182647] text-white' : 'hover:bg-[#182647] hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('suppliers.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/>
                    </svg>
                    <span>Suppliers</span>
                </a>
                @endif

                <!-- Store Stock Dropdown -->
                @if (auth()->user()->hasPermission('stock.view'))
                <div>
                    <button onclick="toggleSubmenu(this)" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-[#182647] hover:text-white transition group focus:outline-none {{ request()->routeIs('stock.index') || request()->routeIs('stock-adjustments.*') || request()->routeIs('damaged-stocks.*') ? 'bg-[#182647]/50 text-white' : '' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-white transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/>
                            </svg>
                            <span>Store Stock</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-500 group-hover:text-white transition chevron-icon {{ request()->routeIs('stock.index') || request()->routeIs('stock-adjustments.*') || request()->routeIs('damaged-stocks.*') ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>
                    <div class="pl-10 pr-2 py-1 space-y-1 bg-[#091024]/40 rounded-lg mt-0.5 {{ request()->routeIs('stock.index') || request()->routeIs('stock-adjustments.*') || request()->routeIs('damaged-stocks.*') || request()->routeIs('stock-transfers.*') || request()->routeIs('barcodes.*') ? '' : 'hidden' }}">
                        <a href="{{ route('stock.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('stock.index') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">Current Stock</a>
                        @if (auth()->user()->hasPermission('stock_transfers.view'))
                        <a href="{{ route('stock-transfers.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('stock-transfers.*') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">Stock Transfers</a>
                        @endif
                        @if (auth()->user()->hasPermission('barcodes.view'))
                        <a href="{{ route('barcodes.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('barcodes.*') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">Print Barcodes</a>
                        @endif
                        @if (auth()->user()->hasPermission('stock.adjustment.view'))
                        <a href="{{ route('stock-adjustments.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('stock-adjustments.*') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">Adjustments</a>
                        @endif
                        @if (auth()->user()->hasPermission('damaged_stock.view'))
                        <a href="{{ route('damaged-stocks.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('damaged-stocks.*') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">Damaged Stock</a>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Expenses Dropdown -->
                @if (auth()->user()->hasPermission('expenses.view') || auth()->user()->hasPermission('reports.expense'))
                <div>
                    <button onclick="toggleSubmenu(this)" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-[#182647] hover:text-white transition group focus:outline-none {{ request()->routeIs('accounting.expenses.*') || request()->routeIs('accounting.expense-categories.*') ? 'bg-[#182647]/50 text-white' : '' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-white transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                            </svg>
                            <span>Expenses</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-500 group-hover:text-white transition chevron-icon {{ request()->routeIs('accounting.expenses.*') || request()->routeIs('accounting.expense-categories.*') ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>
                    <div class="pl-10 pr-2 py-1 space-y-1 bg-[#091024]/40 rounded-lg mt-0.5 {{ request()->routeIs('accounting.expenses.*') || request()->routeIs('accounting.expense-categories.*') ? '' : 'hidden' }}">
                        <a href="{{ route('accounting.expenses.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('accounting.expenses.index') && !request('action') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">List Expenses</a>
                        <a href="{{ route('accounting.expenses.index', ['action' => 'add']) }}" class="block py-2 text-xs font-medium {{ request('action') == 'add' ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">Add Expense</a>
                        <a href="{{ route('accounting.expense-categories.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('accounting.expense-categories.*') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">Categories</a>
                    </div>
                </div>
                @endif

                <!-- Accounting Dropdown -->
                @if (auth()->user()->hasPermission('accounting.view') || auth()->user()->hasPermission('cashbook.view') || auth()->user()->hasPermission('bank.view') || auth()->user()->hasPermission('t_account.view') || auth()->user()->hasPermission('reports.profit_loss'))
                <div>
                    <button onclick="toggleSubmenu(this)" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-[#182647] hover:text-white transition group focus:outline-none {{ request()->routeIs('accounting.*') && !request()->routeIs('accounting.expenses.*') ? 'bg-[#182647]/50 text-white' : '' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-white transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><line x1="12" y1="6" x2="12" y2="18"/><line x1="6" y1="12" x2="18" y2="12"/>
                            </svg>
                            <span>Accounting</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-500 group-hover:text-white transition chevron-icon {{ request()->routeIs('accounting.*') && !request()->routeIs('accounting.expenses.*') ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>
                    <div class="pl-10 pr-2 py-1 space-y-1 bg-[#091024]/40 rounded-lg mt-0.5 {{ request()->routeIs('accounting.*') && !request()->routeIs('accounting.expenses.*') ? '' : 'hidden' }}">
                        @if (auth()->user()->hasPermission('accounting.view') || auth()->user()->hasPermission('reports.profit_loss'))
                        <a href="{{ route('accounting.accounts.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('accounting.accounts.*') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">Chart of Accounts</a>
                        @endif
                        @if (auth()->user()->hasPermission('cashbook.view') || auth()->user()->hasPermission('reports.cash_bank'))
                        <a href="{{ route('accounting.journal-entries.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('accounting.journal-entries.*') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">Journal Entries</a>
                        @endif
                        @if (auth()->user()->hasPermission('bank.view') || auth()->user()->hasPermission('reports.cash_bank'))
                        <a href="{{ route('accounting.ledger.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('accounting.ledger.*') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">Ledger Book</a>
                        @endif
                        @if (auth()->user()->hasPermission('t_account.view') || auth()->user()->hasPermission('reports.profit_loss'))
                        <a href="{{ route('accounting.capital-drawings.index') }}" class="block py-2 text-xs font-medium {{ request()->routeIs('accounting.capital-drawings.*') ? 'text-blue-400 font-semibold' : 'text-slate-400 hover:text-white' }}">Capital & Drawings</a>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Reports Link -->
                @if ($canAccessReports)
                <div>
                    <button onclick="toggleSubmenu(this)" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-[#182647] hover:text-white transition group focus:outline-none {{ request()->routeIs('reports.*') ? 'bg-[#182647]/50 text-white' : '' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-white transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v1a3 3 0 106 0v-1m-6 0a3 3 0 006 0m-6 0V9a3 3 0 013-3v0a3 3 0 013 3v8M5 21h14M5 21a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2H5z"></path></svg>
                            <span>Reports</span>
                        </div>
                        <svg class="w-4 h-4 text-slate-500 group-hover:text-white transition chevron-icon {{ request()->routeIs('reports.*') ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div class="{{ request()->routeIs('reports.*') ? '' : 'hidden' }} mt-1 space-y-1 pl-11">
                        @include('layouts.app.partials.reports-nav')
                    </div>
                </div>
                @endif

                <!-- Stores Link -->
                @if (auth()->user()->hasPermission('stores.view'))
                <a href="{{ route('stores.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('stores.*') ? 'bg-[#182647] text-white' : 'hover:bg-[#182647] hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('stores.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    <span>Store Locations</span>
                </a>
                @endif

                <!-- Settings Link -->
                @if (auth()->user()->hasPermission('settings.view'))
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('profile.*') ? 'bg-[#182647] text-white' : 'hover:bg-[#182647] hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('profile.*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    <span>Settings</span>
                </a>
                @endif
            </nav>
        </div>

        <!-- Sidebar Bottom: POS Shortcuts & User Details -->
        <div class="shrink-0 border-t border-[#182647] bg-[#091024]/20">
            <!-- POS Shortcuts Grid -->
            @if (auth()->user()->hasPermission('retail_pos.view') || auth()->user()->hasPermission('wholesale_pos.view'))
            <div class="p-4 grid grid-cols-2 gap-2.5">
                @if (auth()->user()->hasPermission('retail_pos.view'))
                <a href="{{ route('pos.retail') }}" class="flex flex-col items-center justify-center gap-1.5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold transition text-xs shadow-lg shadow-blue-500/10">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                    <span>Retail POS</span>
                </a>
                @endif
                @if (auth()->user()->hasPermission('wholesale_pos.view'))
                <a href="{{ route('pos.wholesale') }}" class="flex flex-col items-center justify-center gap-1.5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold transition text-xs shadow-lg shadow-emerald-500/10">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <rect x="2" y="3" width="20" height="12" rx="2" />
                        <path d="M12 15v4" />
                        <path d="M9 19h6" />
                        <path d="M19 8h-2l-1.5-2H8.5L7 8H5" />
                        <circle cx="10" cy="8" r="1" />
                        <circle cx="14" cy="8" r="1" />
                    </svg>
                    <span>Wholesale POS</span>
                </a>
                @endif
            </div>
            @endif

            <!-- User Menu -->
            <div class="p-4 border-t border-[#182647] bg-[#091024] flex items-center justify-between relative group">
                <div class="flex items-center gap-3">
                    <div class="h-9 w-9 rounded-full bg-blue-500 text-white font-bold flex items-center justify-center text-sm ring-2 ring-blue-500/20">
                        {{ auth()->user()->initials() }}
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-white leading-none">{{ auth()->user()->name }}</h4>
                        <p class="text-[10px] text-slate-400 font-semibold mt-1">{{ auth()->user()->roles->first()->name ?? 'No Role' }}</p>
                    </div>
                </div>
                <!-- Profile actions dropdown context menu (toggled via css group-hover or click) -->
                <div class="relative">
                    <button onclick="toggleUserMenu()" class="text-slate-400 hover:text-white focus:outline-none cursor-pointer">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                        </svg>
                    </button>
                    <div id="user-menu-dropdown" class="hidden absolute bottom-12 right-0 w-48 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 shadow-xl py-2 z-50 text-slate-700 dark:text-slate-200">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2.5 text-xs font-medium hover:bg-slate-50 dark:hover:bg-slate-900 transition flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                            </svg>
                            <span>Settings</span>
                        </a>
                        <hr class="border-slate-100 dark:border-slate-800 my-1">
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2.5 text-xs font-semibold text-red-600 hover:bg-slate-50 dark:hover:bg-slate-900 transition flex items-center gap-2 cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                                </svg>
                                <span>Log out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Mobile Navigation Top Bar (Hidden on desktop) -->
    <header class="w-full bg-[#0c1630] border-b border-[#182647] flex items-center justify-between px-4 py-3 text-white lg:hidden z-40 sticky top-0 shrink-0">
        <div class="flex items-center gap-3">
            <button onclick="toggleMobileSidebar()" class="text-slate-300 hover:text-white focus:outline-none cursor-pointer">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
            <span class="text-sm font-bold tracking-wide">Phone Shop POS</span>
        </div>
        <div class="flex items-center gap-3">
            <div class="h-8 w-8 rounded-full bg-blue-500 text-white font-bold flex items-center justify-center text-xs">
                {{ auth()->user()->initials() }}
            </div>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-red-400 hover:text-red-500 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                </button>
            </form>
        </div>
    </header>

    <!-- Mobile Off-Canvas Sidebar Menu overlay -->
    <div id="mobile-sidebar" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-50 hidden lg:hidden">
        <div class="w-64 bg-[#0c1630] h-full flex flex-col shadow-2xl relative border-r border-[#182647]">
            <button onclick="toggleMobileSidebar()" class="absolute top-4 right-4 text-slate-400 hover:text-white cursor-pointer z-50">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
            
            <div class="flex-1 overflow-y-auto custom-scrollbar pt-12">
                <!-- Nav Links replicated inside mobile panel -->
                <nav class="p-4 space-y-1">
                    @if (auth()->user()->hasPermission('dashboard.view'))
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/10' : 'text-slate-350 hover:bg-[#182647] hover:text-white' }}">
                        <span>Dashboard</span>
                    </a>
                    @endif

                    @if (auth()->user()->hasPermission('users.view') || auth()->user()->hasPermission('roles.view') || auth()->user()->hasPermission('permissions.view') || auth()->user()->hasPermission('activity_logs.view'))
                    <div class="py-1">
                        <span class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">User Management</span>
                        <div class="mt-1 space-y-1 pl-3">
                            @if (auth()->user()->hasPermission('users.view'))
                            <a href="{{ route('users.index') }}" class="block py-1.5 px-3 rounded text-xs font-medium {{ request()->routeIs('users.*') ? 'text-white bg-[#182647]' : 'text-slate-400 hover:text-white' }}">Users</a>
                            @endif
                            @if (auth()->user()->hasPermission('roles.view'))
                            <a href="{{ route('roles.index') }}" class="block py-1.5 px-3 rounded text-xs font-medium {{ request()->routeIs('roles.*') ? 'text-white bg-[#182647]' : 'text-slate-400 hover:text-white' }}">Roles</a>
                            @endif
                            @if (auth()->user()->hasPermission('permissions.view'))
                            <a href="{{ route('permissions.index') }}" class="block py-1.5 px-3 rounded text-xs font-medium {{ request()->routeIs('permissions.*') ? 'text-white bg-[#182647]' : 'text-slate-400 hover:text-white' }}">Permissions</a>
                            @endif
                            @if (auth()->user()->hasPermission('activity_logs.view'))
                            <a href="{{ route('activity-logs.index') }}" class="block py-1.5 px-3 rounded text-xs font-medium {{ request()->routeIs('activity-logs.*') ? 'text-white bg-[#182647]' : 'text-slate-400 hover:text-white' }}">Activity Logs</a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if (auth()->user()->hasPermission('products.view'))
                    <div class="py-1">
                        <span class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Products</span>
                        <div class="mt-1 space-y-1 pl-3">
                            <a href="{{ route('products.index') }}" class="block py-1.5 px-3 rounded text-xs font-medium {{ request()->routeIs('products.index') ? 'text-white bg-[#182647]' : 'text-slate-400 hover:text-white' }}">All Products</a>
                            <a href="{{ route('categories.index') }}" class="block py-1.5 px-3 rounded text-xs font-medium {{ request()->routeIs('categories.*') ? 'text-white bg-[#182647]' : 'text-slate-400 hover:text-white' }}">Categories</a>
                            <a href="{{ route('brands.index') }}" class="block py-1.5 px-3 rounded text-xs font-medium {{ request()->routeIs('brands.*') ? 'text-white bg-[#182647]' : 'text-slate-400 hover:text-white' }}">Brands</a>
                            <a href="{{ route('units.index') }}" class="block py-1.5 px-3 rounded text-xs font-medium {{ request()->routeIs('units.*') ? 'text-white bg-[#182647]' : 'text-slate-400 hover:text-white' }}">Units</a>
                        </div>
                    </div>
                    @endif

                    @if (auth()->user()->hasPermission('sales.view'))
                    <div class="py-1">
                        <span class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Sales</span>
                        <div class="mt-1 space-y-1 pl-3">
                            <a href="{{ route('sales.index') }}" class="block py-1.5 px-3 rounded text-xs font-medium {{ request()->routeIs('sales.*') ? 'text-white bg-[#182647]' : 'text-slate-400 hover:text-white' }}">Sales Ledger</a>
                            @if (auth()->user()->hasPermission('sales_return.view'))
                            <a href="{{ route('sales-returns.index') }}" class="block py-1.5 px-3 rounded text-xs font-medium {{ request()->routeIs('sales-returns.*') ? 'text-white bg-[#182647]' : 'text-slate-400 hover:text-white' }}">Returns</a>
                            @endif
                            @if (auth()->user()->hasPermission('hold_sales.view'))
                            <a href="{{ route('hold-sales.index') }}" class="block py-1.5 px-3 rounded text-xs font-medium {{ request()->routeIs('hold-sales.*') ? 'text-white bg-[#182647]' : 'text-slate-400 hover:text-white' }}">Hold Sales</a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if (auth()->user()->hasPermission('purchase.view'))
                    <div class="py-1">
                        <span class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Purchase</span>
                        <div class="mt-1 space-y-1 pl-3">
                            <a href="{{ route('purchases.index') }}" class="block py-1.5 px-3 rounded text-xs font-medium {{ request()->routeIs('purchases.*') ? 'text-white bg-[#182647]' : 'text-slate-400 hover:text-white' }}">Purchases</a>
                            @if (auth()->user()->hasPermission('purchase.return'))
                            <a href="{{ route('purchase-returns.index') }}" class="block py-1.5 px-3 rounded text-xs font-medium {{ request()->routeIs('purchase-returns.*') ? 'text-white bg-[#182647]' : 'text-slate-400 hover:text-white' }}">Returns</a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if (auth()->user()->hasPermission('stock.view'))
                    <div class="py-1">
                        <span class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Store Stock</span>
                        <div class="mt-1 space-y-1 pl-3">
                            <a href="{{ route('stock.index') }}" class="block py-1.5 px-3 rounded text-xs font-medium {{ request()->routeIs('stock.index') ? 'text-white bg-[#182647]' : 'text-slate-400 hover:text-white' }}">Current Stock</a>
                            @if (auth()->user()->hasPermission('stock.adjustment.view'))
                            <a href="{{ route('stock-adjustments.index') }}" class="block py-1.5 px-3 rounded text-xs font-medium {{ request()->routeIs('stock-adjustments.*') ? 'text-white bg-[#182647]' : 'text-slate-400 hover:text-white' }}">Adjustments</a>
                            @endif
                            @if (auth()->user()->hasPermission('damaged_stock.view'))
                            <a href="{{ route('damaged-stocks.index') }}" class="block py-1.5 px-3 rounded text-xs font-medium {{ request()->routeIs('damaged-stocks.*') ? 'text-white bg-[#182647]' : 'text-slate-400 hover:text-white' }}">Damaged Stock</a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if (auth()->user()->hasPermission('customers.view'))
                    <a href="{{ route('customers.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('customers.*') ? 'text-white bg-[#182647]' : 'text-slate-350 hover:bg-[#182647] hover:text-white' }}">Customers</a>
                    @endif
                    @if (auth()->user()->hasPermission('suppliers.view'))
                    <a href="{{ route('suppliers.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('suppliers.*') ? 'text-white bg-[#182647]' : 'text-slate-350 hover:bg-[#182647] hover:text-white' }}">Suppliers</a>
                    @endif

                    @if (auth()->user()->hasPermission('expenses.view') || auth()->user()->hasPermission('reports.expense'))
                    <a href="{{ route('accounting.expenses.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('accounting.expenses.*') ? 'text-white bg-[#182647]' : 'text-slate-350 hover:bg-[#182647] hover:text-white' }}">Expenses</a>
                    @endif

                    @if ($canAccessReports)
                    <div>
                        <button onclick="toggleSubmenu(this)" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium hover:bg-[#182647] hover:text-white transition group focus:outline-none {{ request()->routeIs('reports.*') ? 'bg-[#182647]/50 text-white' : 'text-slate-350' }}">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-slate-400 group-hover:text-white transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v1a3 3 0 106 0v-1m-6 0a3 3 0 006 0m-6 0V9a3 3 0 013-3v0a3 3 0 013 3v8M5 21h14M5 21a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2H5z"></path></svg>
                                <span>Reports</span>
                            </div>
                            <svg class="w-4 h-4 text-slate-500 group-hover:text-white transition chevron-icon {{ request()->routeIs('reports.*') ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div class="{{ request()->routeIs('reports.*') ? '' : 'hidden' }} mt-1 space-y-1 pl-11">
                            @include('layouts.app.partials.reports-nav')
                        </div>
                    </div>
                    @endif
                </nav>
            </div>
            
            <div class="shrink-0 border-t border-[#182647] bg-[#091024]/20">
                @if (auth()->user()->hasPermission('retail_pos.view') || auth()->user()->hasPermission('wholesale_pos.view'))
                <div class="p-4 grid grid-cols-2 gap-2">
                    @if (auth()->user()->hasPermission('retail_pos.view'))
                    <a href="{{ route('pos.retail') }}" class="flex items-center justify-center gap-1 py-2 rounded-lg bg-blue-600 text-white text-xs font-semibold">Retail POS</a>
                    @endif
                    @if (auth()->user()->hasPermission('wholesale_pos.view'))
                    <a href="{{ route('pos.wholesale') }}" class="flex items-center justify-center gap-1 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold">Wholesale POS</a>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Main Content Area Wrapper -->
    <main class="flex-1 flex flex-col min-h-screen bg-slate-50 dark:bg-slate-900 overflow-x-hidden">
        {{ $slot }}
    </main>

    <!-- UI Interaction Scripts -->
    <script>
        // Toggle side menu submenus
        function toggleSubmenu(button) {
            const submenu = button.nextElementSibling;
            const chevron = button.querySelector('.chevron-icon');
            
            if (submenu.classList.contains('hidden')) {
                submenu.classList.remove('hidden');
                chevron.classList.add('rotate-180');
            } else {
                submenu.classList.add('hidden');
                chevron.classList.remove('rotate-180');
            }
        }

        // Toggle user dropdown settings menu
        function toggleUserMenu() {
            const dropdown = document.getElementById('user-menu-dropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close user dropdown if clicking outside
        window.addEventListener('click', function(e) {
            const dropdown = document.getElementById('user-menu-dropdown');
            if (dropdown && !dropdown.classList.contains('hidden') && !e.target.closest('.group') && !e.target.closest('#user-menu-dropdown')) {
                dropdown.classList.add('hidden');
            }
        });

        // Toggle mobile menu drawer
        function toggleMobileSidebar() {
            const mobileSidebar = document.getElementById('mobile-sidebar');
            mobileSidebar.classList.toggle('hidden');
        }
    </script>
    
    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
    <script>
        function forceLightTheme() {
            document.documentElement.classList.remove('dark');
            document.documentElement.classList.add('light');
            localStorage.setItem('flux-appearance', 'light');
            localStorage.setItem('theme', 'light');
        }
        forceLightTheme();
        window.addEventListener('DOMContentLoaded', forceLightTheme);
        window.addEventListener('livewire:navigated', forceLightTheme);
    </script>
</body>
</html>
