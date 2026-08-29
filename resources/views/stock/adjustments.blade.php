<x-layouts::app :title="__('Stock Adjustments')">
    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto bg-slate-50">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800 tracking-tight">Stock Adjustments</h1>
                <p class="text-sm text-slate-500 mt-1">History of stock adjustments and their items.</p>
            </div>
            
            @if(auth()->user()->hasPermission('stock.adjustment.create'))
            <a href="{{ route('stock-adjustments.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-full transition shadow-sm cursor-pointer whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>New Adjustment</span>
            </a>
            @endif
        </div>

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

        <!-- Filter Bar -->
        <div class="bg-white rounded-xl border border-slate-200/60 p-4 shadow-sm">
            <form method="GET" action="{{ route('stock-adjustments.index') }}" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="w-full md:w-1/4">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Start Date</label>
                    <input 
                        type="date" 
                        name="start_date"
                        value="{{ request('start_date') }}"
                        placeholder="yyyy-mm-dd"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 text-slate-800 transition"
                    />
                </div>
                
                <div class="w-full md:w-1/4">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">End Date</label>
                    <input 
                        type="date" 
                        name="end_date"
                        value="{{ request('end_date') }}"
                        placeholder="yyyy-mm-dd"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 text-slate-800 transition"
                    />
                </div>

                <div class="w-full md:w-1/4">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">User</label>
                    <select name="user_id" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 text-slate-800 transition bg-white">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2 w-full md:w-auto">
                    <button type="submit" class="px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-lg text-sm transition cursor-pointer">
                        Filter
                    </button>
                    <a href="{{ route('stock-adjustments.index') }}" class="px-5 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 font-medium rounded-lg text-sm text-center transition cursor-pointer">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Adjustments Table -->
        <div class="bg-white rounded-xl border border-slate-200/60 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-200/60 text-slate-400 text-[10px] font-bold uppercase tracking-wider">
                            <th class="px-6 py-4">ID</th>
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4">User</th>
                            <th class="px-6 py-4">Items</th>
                            <th class="px-6 py-4">Notes</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 text-sm">
                        @forelse($adjustments as $adj)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4 text-slate-500">
                                #{{ str_replace('ADJ-', '', $adj->adjustment_no) }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $adj->adjustment_date ? $adj->adjustment_date->format('d M Y, H:i') : '-' }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $adj->creator ? $adj->creator->name : 'System' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-1.5">
                                    @foreach($adj->items as $item)
                                        <div class="text-xs">
                                            <span class="font-semibold text-slate-700 truncate max-w-[120px] inline-block align-bottom" title="{{ $item->product ? $item->product->name : 'Unknown' }}">
                                                {{ $item->product ? $item->product->name : 'Unknown' }}
                                            </span>
                                            <span class="font-bold ml-1 {{ $item->quantity > 0 ? 'text-emerald-600' : ($item->quantity < 0 ? 'text-rose-600' : 'text-slate-500') }}">
                                                {{ $item->quantity > 0 ? '+' : '' }}{{ floatval($item->quantity) }}
                                            </span>
                                            <span class="text-slate-400 ml-1">→ Final: {{ floatval($item->new_stock) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-500 max-w-xs truncate">
                                {{ $adj->notes ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-right font-medium">
                                <a href="{{ route('stock-adjustments.show', $adj->id) }}" class="text-teal-600 hover:text-teal-800 transition mr-2">View</a>
                                <!-- Edit and Delete are placeholders matching design as standard adjustments are immutable -->
                                <a href="#" onclick="Swal.fire('Info', 'Approved stock adjustments cannot be edited to maintain ledger integrity.', 'info'); return false;" class="text-orange-500 hover:text-orange-700 transition mr-2">Edit</a>
                                <a href="#" onclick="Swal.fire('Info', 'Approved stock adjustments cannot be deleted to maintain ledger integrity.', 'info'); return false;" class="text-red-500 hover:text-red-700 transition">Delete</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                <span class="font-medium">No stock adjustments found.</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($adjustments->hasPages())
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200/60">
                {{ $adjustments->links() }}
            </div>
            @endif
        </div>
    </div>
</x-layouts::app>
