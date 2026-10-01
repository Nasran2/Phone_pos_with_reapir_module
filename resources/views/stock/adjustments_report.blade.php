<x-layouts::app :title="__('Adjustments Report')">
    <!-- Header Section -->
    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto bg-slate-50">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-serif text-black tracking-tight">Adjustments Report</h1>
                <p class="text-sm text-black mt-1">Detailed item-level report of all stock adjustments.</p>
            </div>
            
            <a href="{{ route('stock-adjustments.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-black text-sm font-medium rounded-full transition shadow-sm cursor-pointer whitespace-nowrap">
                View History
            </a>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white rounded-xl border border-slate-200/60 p-4 shadow-sm">
            <form method="GET" action="{{ route('stock-adjustments.report') }}" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="w-full md:w-1/5">
                    <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">Start Date</label>
                    <input 
                        type="date" 
                        name="start_date"
                        value="{{ request('start_date') }}"
                        placeholder="yyyy-mm-dd"
                        class="w-full px-3 py-2 border border-slate-900 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 text-black transition"
                    />
                </div>
                
                <div class="w-full md:w-1/5">
                    <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">End Date</label>
                    <input 
                        type="date" 
                        name="end_date"
                        value="{{ request('end_date') }}"
                        placeholder="yyyy-mm-dd"
                        class="w-full px-3 py-2 border border-slate-900 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 text-black transition"
                    />
                </div>

                <div class="w-full md:w-1/5">
                    <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">Store</label>
                    <select name="store_id" class="w-full px-3 py-2 border border-slate-900 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 text-black transition bg-white" disabled>
                        <option value="">All Stores</option>
                        <option value="main" selected>Main Store</option>
                    </select>
                </div>

                <div class="w-full md:w-1/5">
                    <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">User</label>
                    <select name="user_id" class="w-full px-3 py-2 border border-slate-900 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 text-black transition bg-white">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2 w-full md:w-auto">
                    <button type="submit" class="px-5 py-2 bg-slate-100 hover:bg-slate-200 text-black font-medium rounded-lg text-sm transition cursor-pointer">
                        Filter
                    </button>
                    <a href="{{ route('stock-adjustments.report') }}" class="px-5 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-black font-medium rounded-lg text-sm text-center transition cursor-pointer">
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
                        <tr class="bg-slate-50/50 border-b border-slate-200/60 text-black text-[10px] font-bold uppercase tracking-wider">
                            <th class="px-6 py-4">Date & ID</th>
                            <th class="px-6 py-4">Product</th>
                            <th class="px-6 py-4">Store & Inventory</th>
                            <th class="px-6 py-4">Movement</th>
                            <th class="px-6 py-4">Reason</th>
                            <th class="px-6 py-4">User</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-black text-sm">
                        @forelse($items as $item)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4">
                                <div>{{ $item->stockAdjustment->adjustment_date ? $item->stockAdjustment->adjustment_date->format('d M Y, H:i') : '-' }}</div>
                                <a href="{{ route('stock-adjustments.show', $item->stockAdjustment->id) }}" class="text-[11px] text-teal-600 hover:underline font-medium">Adj #{{ str_replace('ADJ-', '', $item->stockAdjustment->adjustment_no) }}</a>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-black">{{ $item->product ? $item->product->name : 'Deleted Product' }}</div>
                                <div class="text-[11px] text-black uppercase tracking-wide">SKU: {{ $item->product ? $item->product->sku : '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-black">
                                Main Store<br>
                                <span class="text-[11px] text-black">Main</span>
                            </td>
                            <td class="px-6 py-4 font-bold">
                                @if($item->quantity > 0)
                                    <span class="text-emerald-600">+{{ floatval($item->quantity) }} {{ $item->product && $item->product->unit ? $item->product->unit->short_name : 'qty' }}</span>
                                @elseif($item->quantity < 0)
                                    <span class="text-rose-600">{{ floatval($item->quantity) }} {{ $item->product && $item->product->unit ? $item->product->unit->short_name : 'qty' }}</span>
                                @else
                                    <span class="text-black">0 {{ $item->product && $item->product->unit ? $item->product->unit->short_name : 'qty' }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-black text-sm">
                                {{ $item->stockAdjustment->reason }}
                            </td>
                            <td class="px-6 py-4 text-black text-sm">
                                {{ $item->stockAdjustment->creator ? $item->stockAdjustment->creator->name : 'System' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-black">
                                <span class="font-medium">No adjustment items found for the selected criteria.</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($items->hasPages())
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200/60">
                {{ $items->links() }}
            </div>
            @endif
        </div>
    </div>
</x-layouts::app>
