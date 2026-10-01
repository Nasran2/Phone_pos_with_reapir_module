<x-layouts::app title="Tax Dashboard">
    <!-- Header Section -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-black tracking-tight">Tax Dashboard</h1>
            <p class="text-sm text-black mt-1 font-medium">Overview of tax liabilities, credits, and payments.</p>
        </div>
        
        <!-- Filter Form -->
        <form action="{{ route('tax.dashboard') }}" method="GET" class="flex items-center gap-3" id="filterForm">
            <select name="date_filter" id="dateFilter" class="border-slate-900 text-black text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 py-2.5 px-4 bg-white shadow-sm font-medium" onchange="document.getElementById('filterForm').submit()">
                <option value="today" {{ $dateFilter === 'today' ? 'selected' : '' }}>Today</option>
                <option value="yesterday" {{ $dateFilter === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                <option value="this_week" {{ $dateFilter === 'this_week' ? 'selected' : '' }}>This Week</option>
                <option value="last_week" {{ $dateFilter === 'last_week' ? 'selected' : '' }}>Last Week</option>
                <option value="this_month" {{ $dateFilter === 'this_month' ? 'selected' : '' }}>This Month</option>
                <option value="last_month" {{ $dateFilter === 'last_month' ? 'selected' : '' }}>Last Month</option>
                <option value="this_year" {{ $dateFilter === 'this_year' ? 'selected' : '' }}>This Year</option>
                <option value="last_year" {{ $dateFilter === 'last_year' ? 'selected' : '' }}>Last Year</option>
                <option value="all_time" {{ $dateFilter === 'all_time' ? 'selected' : '' }}>All Time</option>
            </select>
        </form>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Card 1 -->
        <div class="relative bg-white rounded-2xl p-6 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border border-slate-100 hover:shadow-[0_8px_30px_rgb(0,0,0,0.12)] hover:-translate-y-1 transition-all duration-300 overflow-hidden group">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity duration-300">
                <svg class="w-16 h-16 text-rose-500 transform group-hover:rotate-12 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h3 class="text-black text-xs font-bold uppercase tracking-widest mb-3 flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-rose-500"></div> Total Payable
            </h3>
            <p class="text-3xl font-extrabold text-black tracking-tight">Rs. {{ number_format($stats['payable_balance'], 2) }}</p>
            <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-rose-400 to-rose-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
        </div>
        
        <!-- Card 2 -->
        <div class="relative bg-white rounded-2xl p-6 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border border-slate-100 hover:shadow-[0_8px_30px_rgb(0,0,0,0.12)] hover:-translate-y-1 transition-all duration-300 overflow-hidden group">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity duration-300">
                <svg class="w-16 h-16 text-emerald-500 transform group-hover:-rotate-12 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h3 class="text-black text-xs font-bold uppercase tracking-widest mb-3 flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-emerald-500"></div> Available Credit
            </h3>
            <p class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-emerald-500 to-teal-600 tracking-tight">Rs. {{ number_format($stats['credit_balance'], 2) }}</p>
            <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-400 to-teal-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
        </div>
        
        <!-- Card 3 -->
        <div class="relative bg-white rounded-2xl p-6 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border border-slate-100 hover:shadow-[0_8px_30px_rgb(0,0,0,0.12)] hover:-translate-y-1 transition-all duration-300 overflow-hidden group">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity duration-300">
                <svg class="w-16 h-16 text-blue-500 transform group-hover:-translate-y-2 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
            </div>
            <h3 class="text-black text-xs font-bold uppercase tracking-widest mb-3 flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-blue-500"></div> Output VAT (Filtered)
            </h3>
            <p class="text-3xl font-extrabold text-black tracking-tight">Rs. {{ number_format($stats['total_output'], 2) }}</p>
            <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-blue-400 to-indigo-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
        </div>
        
        <!-- Card 4 -->
        <div class="relative bg-white rounded-2xl p-6 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border border-slate-100 hover:shadow-[0_8px_30px_rgb(0,0,0,0.12)] hover:-translate-y-1 transition-all duration-300 overflow-hidden group">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity duration-300">
                <svg class="w-16 h-16 text-indigo-500 transform group-hover:-translate-y-2 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
            <h3 class="text-black text-xs font-bold uppercase tracking-widest mb-3 flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-indigo-500"></div> Input VAT (Filtered)
            </h3>
            <p class="text-3xl font-extrabold text-black tracking-tight">Rs. {{ number_format($stats['total_input'], 2) }}</p>
            <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-indigo-400 to-purple-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
        </div>
    </div>
    
    <!-- Active Period Detail -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden mb-8 relative">
        <!-- Soft gradient background decorative elements -->
        <div class="absolute top-0 left-0 w-64 h-64 bg-blue-50 rounded-full mix-blend-multiply filter blur-3xl opacity-50 -translate-x-1/2 -translate-y-1/2 pointer-events-none"></div>
        <div class="absolute bottom-0 right-0 w-64 h-64 bg-indigo-50 rounded-full mix-blend-multiply filter blur-3xl opacity-50 translate-x-1/2 translate-y-1/2 pointer-events-none"></div>

        <div class="relative p-6 border-b border-slate-100 flex justify-between items-center bg-white/50 backdrop-blur-sm z-10">
            <h2 class="text-lg font-bold text-black flex items-center gap-3">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                Current Open Tax Period
            </h2>
            @if($activePeriod)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-gradient-to-r from-emerald-100 to-teal-100 text-teal-800 shadow-sm border border-teal-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-teal-500 mr-2 animate-pulse"></span>
                    {{ $activePeriod->period_name }}
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-black border border-slate-200">
                    No Open Period
                </span>
            @endif
        </div>
        
        <div class="relative p-8 z-10">
            @if($activePeriod)
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                    <div class="bg-slate-50/80 rounded-2xl p-5 border border-slate-100 backdrop-blur-sm transition hover:bg-slate-50">
                        <p class="text-xs font-bold text-black uppercase tracking-widest mb-2 flex items-center gap-2"><svg class="w-4 h-4 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg> Period Range</p>
                        <p class="font-bold text-black text-sm mt-1">{{ $activePeriod->start_date->format('M d, Y') }} - {{ $activePeriod->end_date->format('M d, Y') }}</p>
                    </div>
                    <div class="bg-slate-50/80 rounded-2xl p-5 border border-slate-100 backdrop-blur-sm transition hover:bg-slate-50">
                        <p class="text-xs font-bold text-black uppercase tracking-widest mb-2 flex items-center gap-2"><svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Opening Payable</p>
                        <p class="font-bold text-black text-lg">Rs. {{ number_format($activePeriod->opening_payable_balance, 2) }}</p>
                    </div>
                    <div class="bg-indigo-50/50 rounded-2xl p-5 border border-indigo-100/50 backdrop-blur-sm transition hover:bg-indigo-50/80">
                        <p class="text-xs font-bold text-indigo-400 uppercase tracking-widest mb-2 flex items-center gap-2"><svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg> Projected Liability</p>
                        <p class="font-bold text-indigo-700 text-xl tracking-tight">Rs. {{ number_format($activePeriod->total_liability, 2) }}</p>
                    </div>
                    <div class="bg-slate-50/80 rounded-2xl p-5 border border-slate-100 backdrop-blur-sm transition hover:bg-slate-50">
                        <p class="text-xs font-bold text-black uppercase tracking-widest mb-2 flex items-center gap-2"><svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Due Date</p>
                        <p class="font-bold text-black text-sm mt-1">{{ $activePeriod->due_date ? $activePeriod->due_date->format('M d, Y') : 'Not Set' }}</p>
                    </div>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-black mb-2">No Active Tax Period</h3>
                    <p class="text-sm text-black max-w-md mx-auto mb-6">There is currently no open tax period to record transactions against. Please close the previous period or create a new one.</p>
                    <a href="{{ route('tax.periods.create') }}" class="px-6 py-2.5 bg-slate-900 text-white rounded-xl hover:bg-slate-800 hover:shadow-lg text-sm font-medium transition-all duration-300 transform hover:-translate-y-0.5">
                        Create New Period
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
