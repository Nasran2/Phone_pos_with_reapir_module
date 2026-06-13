<x-layouts::app :title="__('Stock Adjustments')">
    <!-- Top Navbar / Header -->
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-slate-800">Stock Adjustments</h2>
            <span class="text-xs bg-blue-100 text-blue-800 font-semibold px-2.5 py-0.5 rounded-full">
                {{ $adjustments->total() }} Records
            </span>
        </div>
        <div>
            @if(auth()->user()->hasPermission('stock.adjustment.create'))
            <a href="{{ route('stock-adjustments.create') }}" class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition duration-150 shadow-sm cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>New Adjustment</span>
            </a>
            @endif
        </div>
    </div>

    <!-- Main Content Panel -->
    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto bg-slate-50">
        <!-- Alerts Block -->
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

        <!-- Filter Bar Card -->
        <div class="bg-white rounded-xl border border-slate-200/60 p-4 shadow-xs">
            <form method="GET" action="{{ route('stock-adjustments.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search bar -->
                <div class="relative">
                    <input 
                        type="text" 
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search Adjustment No, reason..." 
                        class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-slate-800 transition"
                    />
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </div>
                </div>

                <!-- Adjustment Type Filter -->
                <div>
                    <select 
                        name="type" 
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-800 transition bg-white"
                    >
                        <option value="">All Adjustment Types</option>
                        <option value="increase" {{ request('type') === 'increase' ? 'selected' : '' }}>Stock Increase</option>
                        <option value="decrease" {{ request('type') === 'decrease' ? 'selected' : '' }}>Stock Decrease</option>
                        <option value="correction" {{ request('type') === 'correction' ? 'selected' : '' }}>Stock Correction</option>
                        <option value="opening_stock" {{ request('type') === 'opening_stock' ? 'selected' : '' }}>Opening Stock Update</option>
                    </select>
                </div>

                <div></div>

                <!-- Submit & Clear Buttons -->
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-lg text-xs transition duration-150 cursor-pointer">
                        Filter
                    </button>
                    <a href="{{ route('stock-adjustments.index') }}" class="py-2 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-xs text-center transition duration-150 cursor-pointer">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Adjustments Table Card -->
        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200/60 text-slate-650 text-[11px] font-bold uppercase tracking-wider">
                            <th class="px-6 py-4">Adjustment No</th>
                            <th class="px-6 py-4">Adjustment Date</th>
                            <th class="px-6 py-4">Type</th>
                            <th class="px-6 py-4">Reason</th>
                            <th class="px-6 py-4 text-center">Adjusted Items</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Created By</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 text-xs">
                        @forelse($adjustments as $adj)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4 font-mono font-bold text-blue-650">
                                <a href="{{ route('stock-adjustments.show', $adj->id) }}" class="hover:underline">
                                    {{ $adj->adjustment_no }}
                                </a>
                            </td>
                            <td class="px-6 py-4 font-semibold text-slate-600">
                                {{ $adj->adjustment_date ? $adj->adjustment_date->format('Y-m-d') : '-' }}
                            </td>
                            <td class="px-6 py-4 font-bold">
                                <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold
                                    @if($adj->adjustment_type === 'increase') bg-emerald-50 text-emerald-700
                                    @elseif($adj->adjustment_type === 'decrease') bg-rose-50 text-rose-700
                                    @elseif($adj->adjustment_type === 'opening_stock') bg-blue-50 text-blue-700
                                    @else bg-slate-100 text-slate-700 @endif
                                ">
                                    {{ str_replace('_', ' ', $adj->adjustment_type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-600 max-w-xs truncate">
                                {{ $adj->reason }}
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-slate-800">
                                {{ $adj->items_count }} products
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Approved
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-500 font-medium">
                                {{ $adj->creator ? $adj->creator->name : 'System' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a 
                                    href="{{ route('stock-adjustments.show', $adj->id) }}"
                                    class="p-1 px-3 bg-slate-800 hover:bg-slate-900 text-white rounded text-[10px] font-bold transition duration-150 shadow-xs inline-flex items-center gap-1 cursor-pointer"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                        <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <span>View</span>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/>
                                </svg>
                                <span class="font-bold">No stock adjustments recorded.</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination block -->
            @if($adjustments->hasPages())
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200/60">
                {{ $adjustments->links() }}
            </div>
            @endif
        </div>
    </div>
</x-layouts::app>
