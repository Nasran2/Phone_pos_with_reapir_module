<x-layouts::app title="Reports Dashboard">
    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto bg-slate-50">
        <h2 class="text-2xl font-bold text-slate-800 mb-6">Reports Dashboard</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Sales Reports -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="bg-blue-50 px-5 py-4 border-b border-blue-100 flex items-center gap-3">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    <h3 class="font-bold text-blue-900 text-lg">Sales Reports</h3>
                </div>
                <div class="p-2">
                    <a href="{{ route('reports.sales') }}" class="block px-4 py-3 hover:bg-slate-50 text-slate-700 hover:text-blue-600 font-medium rounded-lg transition">All Sales Report</a>
                    <a href="{{ route('reports.retail_sales') }}" class="block px-4 py-3 hover:bg-slate-50 text-slate-700 hover:text-blue-600 font-medium rounded-lg transition">Retail Sales Report</a>
                    <a href="{{ route('reports.wholesale_sales') }}" class="block px-4 py-3 hover:bg-slate-50 text-slate-700 hover:text-blue-600 font-medium rounded-lg transition">Wholesale Sales Report</a>
                    <a href="{{ route('reports.sales_returns') }}" class="block px-4 py-3 hover:bg-slate-50 text-slate-700 hover:text-blue-600 font-medium rounded-lg transition">Sales Return Report</a>
                </div>
            </div>

            <!-- Purchase Reports -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="bg-emerald-50 px-5 py-4 border-b border-emerald-100 flex items-center gap-3">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <h3 class="font-bold text-emerald-900 text-lg">Purchase Reports</h3>
                </div>
                <div class="p-2">
                    <a href="{{ route('reports.purchases') }}" class="block px-4 py-3 hover:bg-slate-50 text-slate-700 hover:text-emerald-600 font-medium rounded-lg transition">Purchase Report</a>
                    <a href="{{ route('reports.purchase_returns') }}" class="block px-4 py-3 hover:bg-slate-50 text-slate-700 hover:text-emerald-600 font-medium rounded-lg transition">Purchase Return Report</a>
                </div>
            </div>

            <!-- Stock Reports -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="bg-orange-50 px-5 py-4 border-b border-orange-100 flex items-center gap-3">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    <h3 class="font-bold text-orange-900 text-lg">Stock Reports</h3>
                </div>
                <div class="p-2">
                    <a href="{{ route('reports.stock') }}" class="block px-4 py-3 hover:bg-slate-50 text-slate-700 hover:text-orange-600 font-medium rounded-lg transition">Stock Report</a>
                    <a href="{{ route('reports.low_stock') }}" class="block px-4 py-3 hover:bg-slate-50 text-slate-700 hover:text-orange-600 font-medium rounded-lg transition">Low Stock Report</a>
                    <a href="{{ route('reports.stock_movement') }}" class="block px-4 py-3 hover:bg-slate-50 text-slate-700 hover:text-orange-600 font-medium rounded-lg transition">Stock Movement Report</a>
                    <a href="{{ route('reports.damaged_stock') }}" class="block px-4 py-3 hover:bg-slate-50 text-slate-700 hover:text-orange-600 font-medium rounded-lg transition">Damaged Stock Report</a>
                </div>
            </div>

            <!-- Finance Reports -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="bg-purple-50 px-5 py-4 border-b border-purple-100 flex items-center gap-3">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <h3 class="font-bold text-purple-900 text-lg">Finance Reports</h3>
                </div>
                <div class="p-2">
                    <a href="{{ route('reports.product_profit') }}" class="block px-4 py-3 hover:bg-slate-50 text-slate-700 hover:text-purple-600 font-medium rounded-lg transition">Product Profit Report</a>
                    <a href="{{ route('reports.profit_loss') }}" class="block px-4 py-3 hover:bg-slate-50 text-slate-700 hover:text-purple-600 font-medium rounded-lg transition">Profit & Loss Report</a>
                    <a href="{{ route('reports.expenses') }}" class="block px-4 py-3 hover:bg-slate-50 text-slate-700 hover:text-purple-600 font-medium rounded-lg transition">Expense Report</a>
                    <a href="{{ route('reports.cash_bank') }}" class="block px-4 py-3 hover:bg-slate-50 text-slate-700 hover:text-purple-600 font-medium rounded-lg transition">Cash & Bank Report</a>
                    <a href="{{ route('reports.tax') }}" class="block px-4 py-3 hover:bg-slate-50 text-slate-700 hover:text-purple-600 font-medium rounded-lg transition">Tax Report</a>
                    <a href="{{ route('reports.daily_closing') }}" class="block px-4 py-3 hover:bg-slate-50 text-slate-700 hover:text-purple-600 font-medium rounded-lg transition text-purple-600 font-bold bg-purple-50/50">Daily Closing</a>
                </div>
            </div>

            <!-- Customer/Supplier Reports -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="bg-indigo-50 px-5 py-4 border-b border-indigo-100 flex items-center gap-3">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <h3 class="font-bold text-indigo-900 text-lg">Customer/Supplier</h3>
                </div>
                <div class="p-2">
                    <a href="{{ route('reports.customer_due') }}" class="block px-4 py-3 hover:bg-slate-50 text-slate-700 hover:text-indigo-600 font-medium rounded-lg transition">Customer Due Report</a>
                    <a href="{{ route('reports.supplier_due') }}" class="block px-4 py-3 hover:bg-slate-50 text-slate-700 hover:text-indigo-600 font-medium rounded-lg transition">Supplier Due Report</a>
                    <a href="{{ route('reports.customer_payments') }}" class="block px-4 py-3 hover:bg-slate-50 text-slate-700 hover:text-indigo-600 font-medium rounded-lg transition">Customer Payment Report</a>
                    <a href="{{ route('reports.supplier_payments') }}" class="block px-4 py-3 hover:bg-slate-50 text-slate-700 hover:text-indigo-600 font-medium rounded-lg transition">Supplier Payment Report</a>
                </div>
            </div>

            <!-- System Reports -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="bg-slate-100 px-5 py-4 border-b border-slate-200 flex items-center gap-3">
                    <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <h3 class="font-bold text-slate-800 text-lg">System Reports</h3>
                </div>
                <div class="p-2">
                    <a href="{{ route('reports.user_activity') }}" class="block px-4 py-3 hover:bg-slate-50 text-slate-700 hover:text-slate-900 font-medium rounded-lg transition">User Activity Report</a>
                </div>
            </div>
            
        </div>
    </div>
</x-layouts::app>
